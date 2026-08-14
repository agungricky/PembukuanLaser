<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EditorRequest;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\File;

class PackingPesananController extends Controller
{
    public function index(Request $request)
    {
        $query = Pesanan::with([
                'toko:id_toko,nama_toko'
            ])
            ->where('status', 'proses')
            ->orderByDesc('tanggal')
            ->orderByDesc('no_pesanan');

        if ($request->filled('no_pesanan')) {

            $keyword = trim($request->no_pesanan);

            $query->where(function ($q) use ($keyword) {

                $q->where('no_pesanan', 'like', "%{$keyword}%")
                  ->orWhere('no_resi', 'like', "%{$keyword}%");

            });

        }

        if ($request->filled('tanggal')) {

            $raw = trim((string) $request->tanggal);

            try {

                if (str_contains($raw, ' s.d ')) {

                    [$startRaw, $endRaw] = explode(' s.d ', $raw, 2);

                    $start = Carbon::parse($startRaw)->startOfDay();
                    $end   = Carbon::parse($endRaw)->endOfDay();

                } else {

                    $start = Carbon::parse($raw)->startOfDay();
                    $end   = Carbon::parse($raw)->endOfDay();

                }

                if ($end->lt($start)) {
                    [$start, $end] = [$end, $start];
                }

                $query->whereBetween('tanggal', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);

            } catch (\Throwable $e) {

                Log::warning('Filter tanggal packing gagal.', [
                    'tanggal' => $raw,
                    'error'   => $e->getMessage(),
                ]);

            }

        }

        $pesanan = $query->get();

        $jumlahPesanan = $pesanan->count();

        $hariIni = Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->count();

        $kurirHariIni = $this->getKurirHariIni();

        return view('packing.pesanan', [
            'pesanan'       => $pesanan,
            'jumlahPesanan' => $jumlahPesanan,
            'hariIni'       => $hariIni,
            'kurirHariIni'  => $kurirHariIni,
        ]);
    }

    public function scan(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => '❌ Auth error',
            ], 401);
        }

        $request->validate([
            'no_pesanan' => ['required', 'string', 'max:100'],
        ]);

        $kode = preg_replace('/\s+/', '', trim($request->no_pesanan));

        Log::info('Packing Scan', [
            'user_id' => Auth::id(),
            'kode'    => $kode,
        ]);

        $pesanan = Pesanan::with('toko')
            ->where('status', 'proses')
            ->where(function ($q) use ($kode) {
                $q->where('no_resi', $kode)
                  ->orWhere('no_pesanan', $kode);
            })
            ->first();

        if (!$pesanan) {

            $pesananLama = Pesanan::where(function ($q) use ($kode) {
                    $q->where('no_resi', $kode)
                      ->orWhere('no_pesanan', $kode);
                })
                ->first();

            if ($pesananLama) {

                return response()->json([
                    'success' => false,
                    'message' => "⚠ Pesanan {$pesananLama->no_pesanan} sudah berstatus {$pesananLama->status}",
                ], 409);

            }

            return response()->json([
                'success' => false,
                'message' => '❌ No. Pesanan / Resi tidak ditemukan',
            ], 404);

        }

        DB::transaction(function () use ($pesanan) {

            $pesanan->status = 'kirim';
            $pesanan->id_user_kirim = Auth::id();
            $pesanan->tanggal_kirim = now();

            $pesanan->save();

        });

        $ekspedisi = $this->detectEkspedisi($pesanan->kurir);

        Log::info('Packing Success', [
            'no_pesanan' => $pesanan->no_pesanan,
            'no_resi'    => $pesanan->no_resi,
            'kurir'      => $pesanan->kurir,
            'user_id'    => Auth::id(),
            'scan_time'  => $pesanan->tanggal_kirim,
        ]);

        $sisaProses = Pesanan::where('status', 'proses')->count();

        $hariIni = Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->count();

        $kurirHariIni = $this->getKurirHariIni();

        return response()->json([

            'success' => true,

            'message' => "✅ {$pesanan->no_pesanan} berhasil dikirim",

            'no_pesanan' => $pesanan->no_pesanan,

            'no_resi' => $pesanan->no_resi,

            'scan_time' => $pesanan->tanggal_kirim->format('H:i:s'),

            'status' => 'kirim',

            'ekspedisi' => $ekspedisi,

            'remaining_proses' => $sisaProses,

            'hari_ini' => $hariIni,

            'kurir_hari_ini' => [

                'spx' => (int) ($kurirHariIni->spx ?? 0),

                'jnt' => (int) ($kurirHariIni->jnt ?? 0),

                'anteraja' => (int) ($kurirHariIni->anteraja ?? 0),

                'jne' => (int) ($kurirHariIni->jne ?? 0),

            ],

        ]);
    }

    public function stats()
    {
        $hariIni = Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->count();

        $totalProses = Pesanan::where('status', 'proses')->count();

        $kurirHariIni = $this->getKurirHariIni();

        return response()->json([
            'hari_ini' => $hariIni,

            'total' => $totalProses,

            'kurir_hari_ini' => [
                'spx'       => (int) ($kurirHariIni->spx ?? 0),
                'jnt'       => (int) ($kurirHariIni->jnt ?? 0),
                'anteraja' => (int) ($kurirHariIni->anteraja ?? 0),
                'jne'       => (int) ($kurirHariIni->jne ?? 0),
            ],
        ]);
    }

    private function getKurirHariIni()
    {
        return Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->selectRaw("
                SUM(CASE WHEN LOWER(kurir) LIKE '%spx%' THEN 1 ELSE 0 END) AS spx,
                SUM(
                    CASE
                        WHEN LOWER(kurir) LIKE '%j&t%'
                          OR LOWER(kurir) LIKE '%jnt%'
                        THEN 1
                        ELSE 0
                    END
                ) AS jnt,
                SUM(CASE WHEN LOWER(kurir) LIKE '%anteraja%' THEN 1 ELSE 0 END) AS anteraja,
                SUM(CASE WHEN LOWER(kurir) LIKE '%jne%' THEN 1 ELSE 0 END) AS jne
            ")
            ->first();
    }

    private function detectEkspedisi(?string $kurir): string
    {
        $kurir = strtolower(trim($kurir ?? ''));

        if (str_contains($kurir, 'spx')) {
            return 'spx';
        }

        if (
            str_contains($kurir, 'j&t') ||
            str_contains($kurir, 'jnt')
        ) {
            return 'jnt';
        }

        if (str_contains($kurir, 'anteraja')) {
            return 'anteraja';
        }

        if (str_contains($kurir, 'jne')) {
            return 'jne';
        }

        return 'unknown';
    }

    public function cariRequest(Request $request)
    {
        $request->validate([
            'request_search' => ['required', 'string', 'max:255'],
        ]);

        $keyword = strtoupper(
            trim((string) $request->request_search)
        );

        $keyword = preg_replace('/\s+/', ' ', $keyword);

        $requests = EditorRequest::with([
                'item.pesanan.toko',
            ])
            ->where('request_search', $keyword)
            ->whereHas('item.pesanan', function ($q) {
                $q->where('status', 'proses');
            })
            ->get();

        if ($requests->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Request customer tidak ditemukan.',
            ], 404);
        }

        $orders = $requests
            ->filter(fn ($item) => $item->item && $item->item->pesanan)
            ->groupBy(fn ($item) => $item->item->no_pesanan);

        if ($orders->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Request yang sama ditemukan pada lebih dari satu pesanan aktif.',
            ], 409);
        }

        $editorRequest = $requests->first();

        if (!$editorRequest->item || !$editorRequest->item->pesanan) {
            return response()->json([
                'success' => false,
                'message' => 'Data pesanan tidak ditemukan.',
            ], 404);
        }

        $pesanan = Pesanan::with([
                'toko',
                'produk.editorRequest',
                'resiPages.import',
            ])
            ->where('no_pesanan', $editorRequest->item->no_pesanan)
            ->where('status', 'proses')
            ->first();

        if (!$pesanan) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan sudah tidak berstatus proses.',
            ], 409);
        }

        $produk = $pesanan->produk->map(function ($item) {
            return [
                'id_per_produk' => $item->id_per_produk,
                'sku' => $item->sku,
                'nama_produk' => $item->nama_produk,
                'variasi' => $item->variasi,
                'jumlah' => (int) $item->jumlah,

                'plat_lengkap' => $item->editorRequest?->plat_lengkap,
                'nama' => $item->editorRequest?->nama,
                'tanggal_bulan_tahun' => $item->editorRequest?->tanggal_bulan_tahun,
            ];
        })->values();

        $resiPages = $pesanan->resiPages
            ->sortBy([
                ['urutan', 'asc'],
                ['halaman', 'asc'],
            ])
            ->values();

        return response()->json([
            'success' => true,

            'request' => [
                'request_search' => $editorRequest->request_search,
                'plat_lengkap' => $editorRequest->plat_lengkap,
                'nama' => $editorRequest->nama,
            ],

            'pesanan' => [
                'no_pesanan' => $pesanan->no_pesanan,
                'no_resi' => $pesanan->no_resi,
                'nama_pembeli' => $pesanan->nama_pembeli,
                'username' => $pesanan->username,
                'kurir' => $pesanan->kurir,
                'toko' => $pesanan->toko?->nama_toko ?? '-',
                'marketplace' => $pesanan->toko?->marketplace ?? '-',

                'produk' => $produk,

                'pdf_tersedia' => $resiPages->isNotEmpty(),

                'halaman_resi' => $resiPages
                    ->pluck('halaman')
                    ->values(),

                'jumlah_halaman_resi' => $resiPages->count(),

                'sudah_print' => !is_null($pesanan->resi_printed_at),

                'print_count' => (int) ($pesanan->resi_print_count ?? 0),
            ],
        ]);
    }

    public function cetakResi(Request $request)
    {
        $request->validate([
            'no_pesanan' => ['required', 'string', 'max:50'],
        ]);

        $pesanan = Pesanan::with([
                'resiPages.import',
            ])
            ->where('no_pesanan', $request->no_pesanan)
            ->where('status', 'proses')
            ->first();

        if (!$pesanan) {
            return back()->with(
                'error',
                'Pesanan tidak ditemukan atau sudah tidak berstatus proses.'
            );
        }

        if ($pesanan->resi_printed_at) {
            return back()->with(
                'error',
                'Resi pesanan ini sudah pernah dicetak.'
            );
        }

        $pages = $pesanan->resiPages
            ->sortBy([
                ['urutan', 'asc'],
                ['halaman', 'asc'],
            ])
            ->values();

        if ($pages->isEmpty()) {
            return back()->with(
                'error',
                'PDF resi untuk pesanan ini belum tersedia.'
            );
        }

        $tempDirectory = storage_path(
            'app/private/print_temp'
        );

        File::ensureDirectoryExists(
            $tempDirectory
        );

        $tempPath = $tempDirectory
            . DIRECTORY_SEPARATOR
            . 'resi_'
            . $pesanan->no_pesanan
            . '_'
            . uniqid()
            . '.pdf';

        try {
            $pdf = new Fpdi();

            foreach ($pages as $page) {
                if (!$page->import) {
                    throw new \Exception(
                        "Data import PDF halaman {$page->halaman} tidak ditemukan."
                    );
                }

                $sourcePath = storage_path(
                    'app/private/' . ltrim(
                        $page->import->path_file,
                        '/'
                    )
                );

                if (!File::exists($sourcePath)) {
                    throw new \Exception(
                        "File PDF sumber tidak ditemukan."
                    );
                }

                $pdf->setSourceFile($sourcePath);

                $template = $pdf->importPage(
                    (int) $page->halaman
                );

                $size = $pdf->getTemplateSize(
                    $template
                );

                $pdf->AddPage(
                    $size['orientation'],
                    [
                        $size['width'],
                        $size['height'],
                    ]
                );

                $pdf->useTemplate(
                    $template
                );
            }

            $pdf->Output(
                'F',
                $tempPath
            );

            DB::transaction(function () use ($pesanan) {
                $now = now();

                if (!$pesanan->resi_printed_at) {
                    $pesanan->resi_printed_at = $now;
                }

                $pesanan->resi_last_printed_at = $now;
                $pesanan->resi_printed_by = Auth::id();

                $pesanan->resi_print_count =
                    ((int) $pesanan->resi_print_count) + 1;

                $pesanan->save();
            });

            return response()
                ->file(
                    $tempPath,
                    [
                        'Content-Type' => 'application/pdf',

                        'Content-Disposition' =>
                            'inline; filename="RESI_'
                            . $pesanan->no_pesanan
                            . '.pdf"',
                    ]
                )
                ->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            File::delete($tempPath);

            Log::error('Cetak resi packing gagal.', [
                'no_pesanan' => $pesanan->no_pesanan,
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Gagal menyiapkan resi: ' . $e->getMessage()
            );
        }
    }
}

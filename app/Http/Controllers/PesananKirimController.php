<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PesananKirimController extends Controller
{
    public function index(Request $request)
    {
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }

        $query = Pesanan::with(['produk', 'user', 'toko'])
            ->where('status', 'kirim')
            ->when($request->filled('q'), function ($qBuilder) use ($request) {
                $keyword = $request->input('q');

                $qBuilder->where(function ($sub) use ($keyword) {
                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('id_toko'), function ($qBuilder) use ($request) {
                $qBuilder->where('id_toko', (int) $request->input('id_toko'));
            })
            ->orderByDesc('tanggal');

        $min = $request->input('min_date');
        $max = $request->input('max_date');

        if ($min || $max) {
            try {
                $start = $min ? Carbon::createFromFormat('Y-m-d', $min)->startOfDay() : null;
                $end = $max ? Carbon::createFromFormat('Y-m-d', $max)->endOfDay() : null;

                if ($start && $end && $end->lt($start)) {
                    [$start, $end] = [
                        $end->copy()->startOfDay(),
                        $start->copy()->endOfDay(),
                    ];
                }

                if ($start && $end) {
                    $query->whereBetween('tanggal', [$start, $end]);
                } elseif ($start) {
                    $query->where('tanggal', '>=', $start);
                } elseif ($end) {
                    $query->where('tanggal', '<=', $end);
                }
            } catch (\Throwable $e) {
                //
            }
        }

        $pesanan = $query->paginate($perPage)->withQueryString();

        foreach ($pesanan as $p) {
            $p->total = (int) $p->produk->sum('jumlah');
            $p->tarik = (float) ($p->total_harga ?? 0) - (float) ($p->total_admin ?? 0);
        }

        $totalItem = (int) $pesanan->sum('total');
        $totalTarik = (float) $pesanan->sum('tarik');

        return view('pesanan.kirim', [
            'pesanan' => $pesanan,
            'jumlahPesanan' => $pesanan->total(),
            'total' => $totalItem,
            'totalTarik' => $totalTarik,
            'perPage' => $perPage,
            'allowed' => $allowedPerPage,
            'daftarToko' => Toko::select('id_toko', 'nama_toko', 'marketplace')
                ->orderBy('nama_toko')
                ->get(),
        ]);
    }

    public function importPage()
    {
        return view('pesanan.import-pencairan');
    }

    public function previewPencairan(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
            'marketplace' => ['required', 'in:Shopee,TikTok'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath() ?: $file->getPathname();
        $marketplace = $request->input('marketplace');

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $data = [];

        foreach ($sheet as $index => $row) {
            if ($marketplace === 'Shopee' && $index < 19) {
                continue;
            }

            if ($marketplace === 'TikTok' && $index < 2) {
                continue;
            }

            if ($marketplace === 'Shopee') {
                $noPesanan = $this->normalizeOrderNumber($row['D'] ?? '');
                $pencairan = $this->cleanNumber($row['F'] ?? 0);
                $notes = '';
            } else {
                $noPesanan = $this->normalizeOrderNumber($row['A'] ?? '');
                $pencairan = $this->cleanNumber($row['F'] ?? 0);
                $notes = '';
            }

            if ($noPesanan === '') {
                continue;
            }

            $pesanan = Pesanan::where('no_pesanan', $noPesanan)->first();

            $data[] = [
                'no_pesanan' => $noPesanan,
                'pencairan' => $pencairan,
                'notes' => $notes,
                'status_db' => $pesanan->status ?? 'tidak ditemukan',
                'ada_di_database' => $pesanan ? true : false,
            ];
        }

        if (empty($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data pencairan yang bisa dibaca.',
            ], 400);
        }

        Session::put('preview_pencairan', $data);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function getPreviewPencairan()
    {
        return response()->json([
            'status' => 'success',
            'data' => Session::get('preview_pencairan', []),
        ]);
    }

    public function simpanPencairan(Request $request)
    {
        $request->validate([
            'data' => ['required', 'array', 'min:1'],
            'data.*.no_pesanan' => ['required', 'string'],
            'data.*.pencairan' => ['nullable', 'numeric'],
            'data.*.notes' => ['nullable', 'string'],
        ]);
    
        DB::beginTransaction();
    
        try {
            $updated = 0;
            $skipped = 0;
    
            foreach ($request->input('data') as $item) {
                $noPesanan = $this->normalizeOrderNumber($item['no_pesanan'] ?? '');
    
                if ($noPesanan === '') {
                    $skipped++;
                    continue;
                }
    
                $pesanan = Pesanan::where('no_pesanan', $noPesanan)
                    ->lockForUpdate()
                    ->first();
    
                if (!$pesanan) {
                    $skipped++;
                    continue;
                }
    
                if ($pesanan->status !== 'affiliate') {
                    $pesanan->status = 'selesai';
                }
    
                $pesanan->pencairan = (float) ($item['pencairan'] ?? 0);
                $pesanan->notes = $item['notes'] ?? null;
                $pesanan->save();
    
                $updated++;
            }
    
            DB::commit();
    
            Session::forget('preview_pencairan');
    
            return response()->json([
                'status' => 'success',
                'updated' => $updated,
                'skipped' => $skipped,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
    
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function ubahStatus(Request $request)
    {
        $validated = $request->validate([
            'no_pesanan' => ['required', 'exists:pesanan,no_pesanan'],
            'status' => ['required', 'in:selesai,pengembalian,pengiriman gagal'],
            'pencairan' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated) {
            $pesanan = Pesanan::where('no_pesanan', $validated['no_pesanan'])
                ->lockForUpdate()
                ->firstOrFail();

            $pesanan->status = $validated['status'];
            $pesanan->pencairan = $validated['pencairan'] ?? null;
            $pesanan->notes = $validated['notes'] ?? null;

            if (in_array($validated['status'], ['pengiriman gagal', 'pengembalian'], true)) {
                $pesanan->total_admin = null;
                $pesanan->total_harga = null;

                DB::table('pesanan_per_produk')
                    ->where('no_pesanan', $validated['no_pesanan'])
                    ->update([
                        'jumlah' => 0,
                    ]);
            }

            $pesanan->save();
        });

        return redirect()
            ->route('pesanan.kirim')
            ->with('success', 'Status pesanan berhasil diubah.');
    }

    private function normalizeOrderNumber($value): string
    {
        $noPesanan = trim((string) $value);

        if ($noPesanan === '') {
            return '';
        }

        if (preg_match('/^\d+(\.0+)?$/', $noPesanan)) {
            $noPesanan = preg_replace('/\.0+$/', '', $noPesanan);
        }

        return $noPesanan;
    }

    private function cleanNumber($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        if ($s === '') {
            return 0.0;
        }

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') === false && substr_count($s, '.') >= 1) {
            $s = str_replace('.', '', $s);
        } elseif (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }
}
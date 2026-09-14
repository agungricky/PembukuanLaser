<?php

namespace App\Services\Gudang;

use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\ResiPage;
use App\Models\stok_produk;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class TransaksiService
{
    public function showdata($filter)
    {
        if ($filter === 'siapkan') {
            $pesanan = Pesanan::where('status', 'proses')->pluck('no_pesanan');

            $pesananPerProduk = PesananPerProduk::with([
                'produk.stok_produk',
            ])
                ->whereIn('no_pesanan', $pesanan)
                ->where('custom', 0)
                ->where('status_pesanan', '0')
                ->get();

            $kebutuhanProduk = $pesananPerProduk
                ->groupBy('sku')
                ->map(function ($items, $sku) {
                    $produk = $items->first()->produk;

                    return [
                        'produk' => $produk,
                        'stok' => $produk?->stok_produk?->jumlah_tersedia ?? 0,
                        'kebutuhan' => $items->sum('jumlah'),
                    ];
                })
                ->sortByDesc(function ($item) {
                    return $item['stok'] >= $item['kebutuhan'];
                })
                ->values();

            return response()->json($kebutuhanProduk);
        } elseif ($filter === 'siap') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk', 'gudang', 'admin_penjualan')
                ->where('jenis_mutasi', 'siap')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'diambil') {
            $kebutuhanProduk = mutasi_stok::with(
                'stok_produk.produk',
                'gudang',
                'admin_penjualan'
            )
                ->where('jenis_mutasi', 'keluar')
                ->where('updated_at', '>=', now()->subMonths(3))
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|array',
            'sku.*' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $pesanan = Pesanan::with('pesanan_per_produk.produk.stok_produk')
                ->where('status', 'proses')
                ->get();

            $kebutuhan = [];
            foreach ($pesanan as $value) {
                foreach ($value->pesanan_per_produk as $item) {

                    // Hanya SKU yang dipilih + belum diproses
                    if ($item->status_pesanan == 0 && in_array($item->sku, $request->sku)) {

                        $sku = $item->sku;
                        $jumlah = $item->jumlah;

                        // =========================
                        // KUMPULKAN TOTAL PER SKU
                        // =========================
                        if (isset($kebutuhan[$sku])) {
                            $kebutuhan[$sku]['jumlah'] += $jumlah;
                            $kebutuhan[$sku]['perproduk_ids'][] = $item->id_per_produk;
                        } else {
                            $kebutuhan[$sku] = [
                                'jumlah' => $jumlah,
                                'stok_produk_id' => $item->produk->stok_produk->id,
                                'perproduk_ids' => [$item->id_per_produk],
                            ];
                        }
                    }
                }
            }

            // =========================
            // BUAT MUTASI STOK
            // =========================
            foreach ($kebutuhan as $sku => $data) {
                $mutasi = mutasi_stok::create([
                    'stok_produk_id' => $data['stok_produk_id'],
                    'gudang_id' => auth()->id(),
                    'jenis_mutasi' => 'siap',
                    'jumlah' => $data['jumlah'],
                    'keterangan' => '',
                ]);

                // =========================
                // UPDATE PESANAN PER PRODUK
                // =========================
                PesananPerProduk::whereIn('id_per_produk', $data['perproduk_ids'])
                    ->update([
                        'status_pesanan' => '1',
                        'mutasi_stok_id' => $mutasi->id,
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil disiapkan',
                'data' => $kebutuhan,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        DB::beginTransaction();
        try {

            foreach ($request->sku as $id) {
                $data = mutasi_stok::find($id);
                $stok = stok_produk::find($data->stok_produk_id);

                if ($data) {
                    $data->jenis_mutasi = 'keluar';
                    $data->adm_penjualan_id = $request->pengambil_barang;
                    $data->save();

                    stok_produk::where('id', $data->stok_produk_id)->update([
                        'jumlah_tersedia' => $stok->jumlah_tersedia - $data->jumlah,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateselesai(Request $request)
    {
        $request->validate([
            'no_pesanan' => 'required',
        ]);

        PesananPerProduk::whereIn('no_pesanan', $request->no_pesanan)->update([
            'status_pesanan' => '1',
        ]);

        Pesanan::whereIn('no_pesanan', $request->no_pesanan)->update([
            'status' => 'kirim',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate',
        ]);
    }

    protected function belumdiImport($noPesanan)
    {
        $resiPages = ResiPage::with('resi_imports')->whereIn('no_pesanan', $noPesanan)->get();
        $pesananDitemukan = $resiPages->pluck('no_pesanan')->unique();
        $pesananTidakDitemukan = collect($noPesanan)->diff($pesananDitemukan);
        $tidakDitemukan = Pesanan::join(
            'pesanan_per_produk',
            'pesanan.no_pesanan',
            '=',
            'pesanan_per_produk.no_pesanan'
        )
            ->whereIn('pesanan.no_pesanan', $pesananTidakDitemukan)
            ->get([
                'pesanan.no_pesanan',
                'pesanan.no_resi',
                'pesanan_per_produk.sku',
            ]);

        if ($pesananTidakDitemukan->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat Pesanan yang Resinya Belum di Import.',
                'tidak_ditemukan' => $tidakDitemukan->values(),
            ], 422);
        }
    }

    public function previewResi($token)
    {
        $folder = 'temp/resi/'.$token;

        if (! Storage::disk('local')->exists($folder)) {
            abort(404, 'Folder resi tidak ditemukan.');
        }

        $files = Storage::disk('local')->files($folder);

        if (empty($files)) {
            abort(404, 'PDF resi tidak ditemukan.');
        }

        $path = Storage::disk('local')->path(
            $files[0]
        );

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-resi.pdf"',
        ]);
    }

    public function cetakResi(Request $request)
    {
        $request->validate([
            'sku' => ['required', 'array', 'min:1'],
            'sku.*' => ['required', 'string'],
        ]);

        $mutasi = mutasi_stok::whereIn('id', $request->sku)->get();

        $noPesanan = PesananPerProduk::whereIn(
            'mutasi_stok_id',
            $mutasi->pluck('id')
        )
            ->distinct()
            ->pluck('no_pesanan');

        $cekImport = $this->belumdiImport($noPesanan);

        if ($cekImport) {
            return $cekImport;
        }

        $resiPages = ResiPage::select(
            'id',
            'no_pesanan',
            'resi_import_id',
            'halaman',
            'urutan'
        )
            ->with([
                'resi_imports:id,path_file,nama_file',
            ])
            ->whereIn('no_pesanan', $noPesanan)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'no_pesanan' => $item->no_pesanan,
                    'resi_import_id' => $item->resi_import_id,
                    'halaman' => $item->halaman,
                    'urutan' => $item->urutan,
                    'path_file' => $item->resi_imports?->path_file,
                    'nama_file' => $item->resi_imports?->nama_file,
                ];
            });

        // BUAT TOKEN
        $token = (string) Str::uuid();

        // FOLDER TEMP
        $tempFolder = 'temp/resi/'.$token;

        Storage::disk('local')->makeDirectory($tempFolder);

        $hasilPotong = [];

        foreach ($resiPages as $item) {

            $sourcePath = Storage::disk('local')->path(
                $item['path_file']
            );

            $pdf = new Fpdi;

            // Baca PDF sumber
            $pageCount = $pdf->setSourceFile($sourcePath);

            // Cek halaman
            if (
                $item['halaman'] < 1 ||
                $item['halaman'] > $pageCount
            ) {
                throw new Exception(
                    'Halaman '.$item['halaman'].
                    ' tidak ditemukan pada file '.
                    $item['nama_file']
                );
            }

            // Ambil halaman
            $templateId = $pdf->importPage(
                $item['halaman']
            );

            $size = $pdf->getTemplateSize(
                $templateId
            );

            // Buat halaman baru
            $pdf->AddPage(
                $size['orientation'],
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate($templateId);

            // Nama file berdasarkan no pesanan
            $namaFile = $item['no_pesanan'].'.pdf';

            $relativePath = $tempFolder.'/'.$namaFile;

            $outputPath = Storage::disk('local')->path(
                $relativePath
            );

            // Simpan PDF
            $pdf->Output(
                'F',
                $outputPath
            );

            $hasilPotong[] = [
                'no_pesanan' => $item['no_pesanan'],
                'halaman' => $item['halaman'],
                'path' => $relativePath,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Resi berhasil diproses.',
            'preview_url' => route(
                'transaksi.preview-resi',
                [
                    'token' => $token,
                ]
            ),
        ]);
    }
}

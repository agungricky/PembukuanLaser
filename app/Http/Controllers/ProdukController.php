<?php

namespace App\Http\Controllers;

use App\Exports\produkExport;
use App\Exports\ProdukPerformaExport;
use App\Imports\produkImport;
use App\Models\kategori;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'store_id' => ['nullable', 'integer'], // id_toko
        ]);

        $startDate = $validated['start_date'] ?? now()->toDateString();
        $endDate = $validated['end_date'] ?? $startDate;
        $storeId = $validated['store_id'] ?? null;

        $start = CarbonImmutable::createFromFormat('Y-m-d', $startDate)->toDateString();
        $end = CarbonImmutable::createFromFormat('Y-m-d', $endDate)->toDateString();
        if ($end < $start) {
            $end = $start;
            $endDate = $startDate;
        }

        // Dropdown toko (array id => nama)
        $stores = DB::table('toko')
            ->select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('nama_toko')
            ->get()
            ->keyBy('id_toko');

        // Nama toko terpilih (aman dari null)
        $storeName = '';
        if ($storeId) {
            $storeName = DB::table('toko')->where('id_toko', $storeId)->value('nama_toko') ?? '';
        }

        // ===== Jika minta download Excel: export semua produk-variasi (sesuai filter)
        if (strtolower($request->query('download', '')) === 'xlsx') {
            $rows = DB::table('pesanan_per_produk as pp')
                ->join('pesanan as p', 'pp.no_pesanan', '=', 'p.no_pesanan')
                ->whereBetween('p.tanggal', [$start, $end])
                ->when($storeId, fn ($q) => $q->where('p.id_toko', $storeId))
                ->selectRaw('pp.nama_produk')
                ->selectRaw('COALESCE(pp.variasi, "-") as variasi')
                ->selectRaw('SUM(pp.jumlah) as total_terjual')
                ->selectRaw('SUM(pp.jumlah * pp.harga) as total_penjualan')
                ->groupBy('pp.nama_produk', 'pp.variasi')
                ->orderBy('pp.nama_produk')
                ->orderBy('variasi')
                ->get()
                ->map(function ($r) {
                    return [
                        $r->nama_produk,
                        $r->variasi,
                        (int) $r->total_terjual,
                        (float) $r->total_penjualan,
                    ];
                })
                ->toArray();

            $file = 'produk_performa_'.
                    ($storeId ? 'toko-'.$storeId.'_' : '').
                    str_replace('-', '', $startDate).'-'.str_replace('-', '', $endDate).
                    '.xlsx';

            return Excel::download(new ProdukPerformaExport($rows), $file);
        }

        // ===== Basis agregasi per-produk (untuk chart & ringkasan di halaman)
        $baseProduct = PesananPerProduk::query()
            ->join('pesanan', 'pesanan_per_produk.no_pesanan', '=', 'pesanan.no_pesanan')
            ->whereBetween('pesanan.tanggal', [$start, $end])
            ->when($storeId, fn ($q) => $q->where('pesanan.id_toko', $storeId))
            ->whereNotNull('pesanan_per_produk.nama_produk')
            ->select([
                'pesanan_per_produk.nama_produk',
                DB::raw('MAX(pesanan_per_produk.harga) as harga_satuan'),
                DB::raw('SUM(pesanan_per_produk.jumlah) as total_terjual'),
                DB::raw('SUM(pesanan_per_produk.jumlah * pesanan_per_produk.harga) as total_penjualan'),
            ])
            ->groupBy('pesanan_per_produk.nama_produk');

        $topByQty = (clone $baseProduct)
            ->orderByDesc('total_terjual')
            ->orderByDesc('total_penjualan')
            ->limit(10)
            ->get();

        // Detail variasi untuk produk di chart (modal)
        $names = $topByQty->pluck('nama_produk')->unique()->values();

        $detailRows = PesananPerProduk::query()
            ->join('pesanan', 'pesanan_per_produk.no_pesanan', '=', 'pesanan.no_pesanan')
            ->whereBetween('pesanan.tanggal', [$start, $end])
            ->when($storeId, fn ($q) => $q->where('pesanan.id_toko', $storeId))
            ->whereIn('pesanan_per_produk.nama_produk', $names)
            ->select([
                'pesanan_per_produk.nama_produk',
                'pesanan_per_produk.variasi',
                DB::raw('MAX(pesanan_per_produk.harga) as harga_satuan'),
                DB::raw('SUM(pesanan_per_produk.jumlah) as total_terjual'),
                DB::raw('SUM(pesanan_per_produk.jumlah * pesanan_per_produk.harga) as total_penjualan'),
            ])
            ->groupBy('pesanan_per_produk.nama_produk', 'pesanan_per_produk.variasi')
            ->orderBy('pesanan_per_produk.nama_produk')
            ->get();

        $detailsMap = [];
        foreach ($detailRows as $r) {
            $detailsMap[$r->nama_produk][] = [
                'variasi' => $r->variasi,
                'total_terjual' => (int) $r->total_terjual,
                'total_penjualan' => (float) $r->total_penjualan,
                'harga_satuan' => (float) $r->harga_satuan,
            ];
        }

        // ===== Summary (ringkasan atas)
        $agg = PesananPerProduk::query()
            ->join('pesanan', 'pesanan_per_produk.no_pesanan', '=', 'pesanan.no_pesanan')
            ->whereBetween('pesanan.tanggal', [$start, $end])
            ->when($storeId, fn ($q) => $q->where('pesanan.id_toko', $storeId))
            ->selectRaw('COALESCE(SUM(pesanan_per_produk.jumlah * pesanan_per_produk.harga),0) as omzet')
            ->selectRaw('COALESCE(SUM(pesanan_per_produk.jumlah),0) as qty')
            ->selectRaw('COUNT(DISTINCT pesanan_per_produk.nama_produk) as produk_aktif')
            ->first();

        $summary = [
            'omzet' => (float) ($agg->omzet ?? 0),
            'qty' => (int) ($agg->qty ?? 0),
            'produk_aktif' => (int) ($agg->produk_aktif ?? 0),
            'avg_price' => (($agg->qty ?? 0) > 0) ? ($agg->omzet / $agg->qty) : 0.0,
        ];

        return view('laporan.produk', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'storeId' => $storeId ? (string) $storeId : '',
            'storeName' => $storeName,
            'stores' => $stores,
            'summary' => $summary,
            'chart' => [
                'labels' => $topByQty->pluck('nama_produk')->values(),
                'data' => $topByQty->pluck('total_terjual')->map(fn ($v) => (int) $v)->values(),
            ],
            'detailsMap' => $detailsMap,
        ]);
    }

    public function export(Request $request)
    {
        $kategori = kategori::where('id', $request->filter)->first();

        if ($kategori == null) {
            $namaFile = 'Daftar Produk - Semua Kategori - '
                .now()->format('d-m-Y')
                .'.xlsx';

            $id = 0;
        } else {
            $namaFile = 'Daftar Produk - '
                            .preg_replace('/[\/\\\\:*?"<>|]/', '-', $kategori->nama_kategori)
                            .' - '
                            .now()->format('d-m-Y')
                            .'.xlsx';
            $id = $kategori->id;
        }

        return Excel::download(new produkExport($id), $namaFile);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $fullPath = null;
        try {
            $file = $request->file('file');
            $folder = storage_path('app/imports');

            if (! is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $namaFile = time().'_'.$file->getClientOriginalName();
            $fileBaru = $file->move(
                $folder,
                $namaFile
            );

            $fullPath = $fileBaru->getPathname();
            $import = new produkImport;
            Excel::import(
                $import,
                $fullPath
            );

            $perubahan = $import->perubahan;
            if (empty($perubahan)) {
                return response()->json([
                    'status' => false,
                    'type' => 'no_changes',
                    'message' => 'Tidak ada perubahan HPP.',
                ]);
            }

            $token = (string) Str::uuid();

            Cache::put(
                'import_produk_'.$token,
                $perubahan,
                now()->addMinutes(10)
            );

            return response()->json([
                'status' => true,
                'jumlah' => count($perubahan),
                'data' => $perubahan,
                'token' => $token,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);

        } finally {
            if ($fullPath && file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function confirmImport(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $key = 'import_produk_'.$request->token;

        $perubahan = Cache::get($key);

        if (! $perubahan) {
            return response()->json([
                'status' => false,
                'message' => 'Data import sudah kadaluarsa. Silakan upload ulang.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            $jumlahUpdate = 0;

            foreach ($perubahan as $item) {

                $produk = Produk::where('sku', $item['sku'])
                    ->lockForUpdate()
                    ->first();

                if (! $produk) {
                    throw new \Exception(
                        'Produk dengan SKU '.$item['sku'].' tidak ditemukan.'
                    );
                }

                // Pastikan data belum berubah sejak preview
                if (
                    round((float) $produk->hpp, 2) !==
                    round((float) $item['hpp_lama'], 2)
                ) {
                    throw new \Exception(
                        'HPP SKU '.$item['sku'].
                        ' sudah berubah sejak proses preview.'
                    );
                }

                $produk->update([
                    'hpp' => $item['hpp_baru'],
                ]);

                $jumlahUpdate++;
            }

            DB::commit();

            Cache::forget($key);

            return response()->json([
                'status' => true,
                'message' => $jumlahUpdate.' produk berhasil diperbarui.',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

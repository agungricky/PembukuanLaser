<?php

namespace App\Services\Gudang;

use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use App\Models\stok_produk;
use Carbon\Carbon;

class DashboardService
{
    public function gudang()
    {
        $produk = Produk::whereNotNull(['nama_produk', 'variasi'])->get();
        $allStok_aman = $produk->count() * 5;
        $stokTersedia = stok_produk::sum('jumlah_tersedia');
        $tanggalHariIni = Carbon::now('Asia/Jakarta')->toDateString();
        $mutasi_masuk = mutasi_stok::where('jenis_mutasi', 'masuk')
            ->whereDate('created_at', $tanggalHariIni)
            ->sum('jumlah');
        $mutasi_keluar = mutasi_stok::where('jenis_mutasi', 'keluar')
            ->orWhere('jenis_mutasi', 'sampel')
            ->whereDate('created_at', $tanggalHariIni)
            ->sum('jumlah');
        $banyakMutasi = mutasi_stok::whereDate('created_at', $tanggalHariIni)->count();
        $stokAman = stok_produk::where('jumlah_tersedia', '>', 5)->count();
        $stokMenipis = stok_produk::whereBetween('jumlah_tersedia', [3, 5])->count();
        $stokKritis = stok_produk::whereBetween('jumlah_tersedia', [1, 2])->count();
        $stokHabis = stok_produk::where('jumlah_tersedia', '=', 0)->count();
        $terlaris = mutasi_stok::with('stok_produk.produk')
            ->where('jenis_mutasi', 'keluar')
            ->orWhere('jenis_mutasi', 'sampel')
            ->get()
            ->groupBy('stok_produk_id')
            ->map(function ($items) {
                $mutasi = $items->first();
                $stok = $mutasi->stok_produk;
                $produk = $stok?->produk;

                if (! $produk) {
                    return null;
                }

                return (object) [
                    'sku' => $produk->sku,
                    'nama_produk' => $produk->nama_produk,
                    'variasi' => $produk->variasi,
                    'jumlah' => $items->sum('jumlah'),
                    'stok_produk' => $stok?->jumlah_tersedia,
                ];
            })
            ->filter()
            ->sortByDesc('jumlah')
            ->values()
            ->take(10);

        $aktivitas = mutasi_stok::with(['stok_produk.produk.kategori'])
            ->whereIn('jenis_mutasi', ['keluar', 'masuk'])
            ->orderBy('created_at', 'DESC')
            ->take(10)
            ->get();

        return view('gudang.Dashboard', [
            'Card' => [
                'allStok_aman' => $allStok_aman,
                'stokTersedia' => $stokTersedia,
                'mutasiMasuk' => $mutasi_masuk,
                'mutasiKeluar' => $mutasi_keluar,
                'banyakMutasi' => $banyakMutasi,
                'stokAman' => $stokAman,
                'stokMenipis' => $stokMenipis,
                'stokKritis' => $stokKritis,
                'stokHabis' => $stokHabis,
            ],
            'Produk' => [
                'terlaris' => $terlaris,
            ],
            'Aktivitas' => [
                'aktivitas' => $aktivitas,
            ],
        ]);
    }

    public function gudanginventory()
    {
        $pesanan = Pesanan::with([
            'pesanan_per_produk' => function ($query) {
                $query->where('custom', 0)
                    ->with('produk');
            },
        ])
            ->where('status', 'proses')
            ->get();

        $kebutuhan = [];
        foreach ($pesanan as $value) {
            foreach ($value->pesanan_per_produk as $item) {
                $sku = $item->sku;
                $jumlah = $item->jumlah;

                if ($item->status_pesanan == 0) {
                    if (isset($kebutuhan[$sku])) {
                        $kebutuhan[$sku] += $jumlah;
                    } else {
                        $kebutuhan[$sku] = $jumlah;
                    }
                }
            }
        }

        $kebutuhanProduk = [];
        foreach ($kebutuhan as $sku => $jumlah) {
            $produk = Produk::with('stok_produk')->where('sku', $sku)->first();
            $kebutuhanProduk[] = [
                'produk' => $produk,
                'kebutuhan' => $jumlah,
            ];
        }

        return response()->json($kebutuhanProduk);

    }

    public function allpesanan($filter)
    {
        if ($filter === 'siapkan') {
            $pesanan = Pesanan::with([
                'pesanan_per_produk' => function ($query) {
                    $query->where('custom', 0)
                        ->with('produk');
                },
            ])
                ->where('status', 'proses')
                ->get();

            $kebutuhan = [];
            foreach ($pesanan as $value) {
                foreach ($value->pesanan_per_produk as $item) {
                    $sku = $item->sku;
                    $jumlah = $item->jumlah;

                    if ($item->status_pesanan == 0) {
                        if (isset($kebutuhan[$sku])) {
                            $kebutuhan[$sku] += $jumlah;
                        } else {
                            $kebutuhan[$sku] = $jumlah;
                        }
                    }
                }
            }

            $kebutuhanProduk = [];
            foreach ($kebutuhan as $sku => $jumlah) {
                $produk = Produk::with('stok_produk')->where('sku', $sku)->first();
                $kebutuhanProduk[] = [
                    'produk' => $produk,
                    'kebutuhan' => $jumlah,
                ];
            }

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'siap') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk')
                ->where('jenis_mutasi', 'siap')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'diambil') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk')
                ->where('jenis_mutasi', 'keluar')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);
        }
    }

    public function detailcard($card)
    {
        $query = stok_produk::with([
            'produk.kategori',
        ]);

        if ($card === 'aman') {
            $query->where('jumlah_tersedia', '>', 5);
        } elseif ($card === 'menipis') {
            $query->whereBetween('jumlah_tersedia', [3, 5]);
        } elseif ($card === 'kritis') {
            $query->whereBetween('jumlah_tersedia', [1, 2]);
        } elseif ($card === 'habis') {
            $query->where('jumlah_tersedia', '=', 0);
        } else {
            return response()->json([
                'message' => 'Filter tidak valid',
            ], 400);
        }
        $data = $query->orderBy('jumlah_tersedia', 'asc')->get();

        return response()->json($data);
    }

    public function detailpesanan($filter, $sku)
    {
        if ($filter === 'siapkan') {
            $data = PesananPerProduk::with('pesanan.toko', 'produk')
                ->where('sku', $sku)
                ->where('status_pesanan', '0')
                ->whereHas('pesanan', function ($query) {
                    $query->where('status', 'proses');
                })->get();

            return response()->json($data);

        } else {
            $kebutuhanProduk = PesananPerProduk::with('pesanan.toko', 'produk')->where('mutasi_stok_id', $sku)->get();

            return response()->json($kebutuhanProduk);

        }
    }
}

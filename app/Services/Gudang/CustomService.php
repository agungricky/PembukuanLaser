<?php

namespace App\Services\Gudang;

use App\Models\kategori;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\stok_produk;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomService
{
    public function produkcustom()
    {
        $kategori = kategori::all();
        return view('gudang.produk_custom', compact('kategori'));
    }

    public function custom_data()
    {
        $tanggalAwal = now()->subDays(6)->startOfDay();
        $tanggalAkhir = now()->endOfDay();
        $marketplace = request('marketplace');
        $kategori = request('kategori');
        $data = Pesanan::query()
            ->with([
                'pesanan_per_produk' => function ($query) {
                    $query
                        ->where('custom', 1)
                        ->where('status_pesanan', '0')
                        ->whereNull('mutasi_stok_id');
                },
                'toko',
            ])
            ->where('status', 'proses')
            ->whereHas('pesanan_per_produk', function ($query) {
                $query
                    ->where('custom', 1)
                    ->where('status_pesanan', '0')
                    ->whereNull('mutasi_stok_id');
            })
            ->whereBetween('tanggal', [
                $tanggalAwal,
                $tanggalAkhir,
            ])
            ->orderByRaw('batas_kirim_at IS NULL ASC')
            ->orderBy('batas_kirim_at', 'ASC');

        if (! empty($marketplace)) {
            $data->whereHas(
                'toko',
                function ($query) use ($marketplace) {
                    $query->where('marketplace', $marketplace);
                }
            );
        }

        if (! empty($kategori)) {
            $data->whereHas(
                'pesanan_per_produk',
                function ($query) use ($kategori) {
                    $query
                        ->where('custom', 1)
                        ->where('status_pesanan', '0')
                        ->whereNull('mutasi_stok_id')
                        ->whereHas('produk',
                            function ($produk) use ($kategori) {
                                $produk->where(
                                    'kategori_id',
                                    $kategori
                                );
                            }
                        );
                }
            );
        }

        $antrian = DB::table('pesanan_per_produk as ppp')
            ->join(
                'pesanan as p',
                'p.no_pesanan',
                '=',
                'ppp.no_pesanan'
            )
            ->where('p.status', 'proses')
            ->where('ppp.status_pesanan', '0')
            ->where('ppp.custom', 1)
            ->whereNull('ppp.mutasi_stok_id')
            ->whereBetween(
                'p.tanggal',
                [
                    $tanggalAwal,
                    $tanggalAkhir,
                ]
            )
            ->select([
                'ppp.id_per_produk',
                'ppp.no_pesanan',
                'ppp.sku',
                'ppp.jumlah',
                'p.batas_kirim_at',
            ])
            ->orderByRaw('p.batas_kirim_at IS NULL ASC')
            ->orderBy('p.batas_kirim_at', 'ASC')
            ->orderBy('ppp.id_per_produk', 'ASC')
            ->get();

        $semuaSku = $antrian
            ->pluck('sku')
            ->filter()
            ->unique()
            ->values();

        $stokProduk = stok_produk::query()
            ->whereIn('sku_id', $semuaSku)
            ->pluck('jumlah_tersedia', 'sku_id');

        $sisaStok = [];
        $alokasiStok = [];
        $statusPesanan = [];
        foreach ($antrian as $item) {
            $sku = $item->sku;
            $jumlah = (int) $item->jumlah;

            if (! isset($sisaStok[$sku])) {
                $sisaStok[$sku] = (int) (
                    $stokProduk[$sku] ?? 0
                );
            }

            $stokSebelum = $sisaStok[$sku];
            $tersedia = $stokSebelum >= $jumlah;
            $sisaStok[$sku] = max(
                0,
                $stokSebelum - $jumlah
            );

            $alokasiStok[$item->id_per_produk] = [
                'stok_awal' => $stokSebelum,
                'jumlah' => $jumlah,
                'stok_sisa' => $sisaStok[$sku],
                'tersedia' => $tersedia,
            ];

            if (! array_key_exists($item->no_pesanan, $statusPesanan)) {
                $statusPesanan[
                    $item->no_pesanan
                ] = true;
            }

            if (! $tersedia) {
                $statusPesanan[
                    $item->no_pesanan
                ] = false;
            }
        }

        $pesananTersedia = array_keys(
            array_filter(
                $statusPesanan
            )
        );
        return DataTables::eloquent($data)
            ->editColumn('pesanan_per_produk',
                function ($pesanan) use ($alokasiStok, $stokProduk) {
                    return $pesanan
                        ->pesanan_per_produk
                        ->map(
                            function ($item) use ($alokasiStok, $stokProduk) {
                                $alokasi =
                                    $alokasiStok[$item->id_per_produk] ?? null;

                                return [
                                    'id_per_produk' => $item->id_per_produk,
                                    'no_pesanan' => $item->no_pesanan,
                                    'sku' => $item->sku,
                                    'nama_produk' => $item->nama_produk,
                                    'variasi' => $item->variasi,
                                    'jumlah' => $item->jumlah,
                                    'stok_total' => (int) (
                                        $stokProduk[$item->sku] ?? 0
                                    ),
                                    'stok_awal' => $alokasi['stok_awal'] ?? 0,
                                    'stok_sisa' => $alokasi['stok_sisa'] ?? 0,
                                    'tersedia' => $alokasi['tersedia'] ?? false,
                                ];
                            }
                        )
                        ->values()
                        ->toArray();
                }
            )
            ->filterColumn(
                'input_at',
                function ($query, $keyword) {
                    $keyword = trim($keyword);
                    $query->where(function ($q) use ($keyword) {
                            $q->where('pesanan.no_pesanan', 'like', $keyword.'%')
                            ->orWhere('pesanan.input_at', 'like', '%'.$keyword.'%');
                        }
                    );
                }
            )
            ->filterColumn('pesanan_per_produk',
                function ($query, $keyword) {
                    $keyword = trim($keyword);
                    $query->whereHas('pesanan_per_produk',
                        function ($q) use ($keyword) {
                            $q->where('custom', 1)
                                ->where('sku', 'like', $keyword.'%');
                        }
                    );
                }
            )
            ->filterColumn('no_resi',
                function ($query, $keyword) {
                    $keyword = trim($keyword);
                    $query->where(
                        function ($q) use ($keyword) {
                            $q->where('pesanan.no_resi', 'like', $keyword.'%')
                                ->orWhereHas('toko',
                                    function ($toko) use ($keyword) {
                                        $toko->where('nama_toko', 'like', '%'.$keyword.'%'); 
                                    }
                                );
                        }
                    );
                }
            )
            ->orderColumn('status_stok',
                function ($query, $order) use ($pesananTersedia) {
                    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
                    $query->reorder();
                    if (! empty($pesananTersedia)) {
                        $placeholders = 
                            implode(
                                ',',
                                array_fill(
                                    0,
                                    count(
                                        $pesananTersedia
                                    ),
                                    '?'
                                )
                            );

                        $query->orderByRaw(
                            "
                        CASE
                            WHEN pesanan.no_pesanan
                                IN ($placeholders)
                            THEN 0
                            ELSE 1
                        END {$order}
                        ",
                            $pesananTersedia
                        );
                    }

                    $query
                        ->orderByRaw(
                            'pesanan.batas_kirim_at IS NULL ASC'
                        )
                        ->orderBy(
                            'pesanan.batas_kirim_at',
                            'ASC'
                        );
                }
            )
            ->orderColumn('input_at',
                function ($query, $order) {
                    $query->reorder()
                        ->orderBy('pesanan.input_at', $order);
                }
            )
            ->toJson();
    }
}

<?php

namespace App\Services\Gudang;

use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\retur;
use App\Models\stok_produk;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturService
{
    public function barangretur(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 10;
        $pesanan = Pesanan::with([
            'pesanan_per_produk.retur',
            'user',
            'toko',
        ])->whereIn('status', [
            'pengembalian',
            'pengiriman gagal',
        ])->whereHas('pesanan_per_produk.retur')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {

                    $q->where('no_pesanan', 'like', '%'.$search.'%')
                        ->orWhere('nama_pembeli', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('no_resi', 'like', '%'.$search.'%')
                        ->orWhere('kurir', 'like', '%'.$search.'%')
                        ->orWhere('status', 'like', '%'.$search.'%')

                        ->orWhereHas('pesanan_per_produk', function ($produk) use ($search) {
                            $produk->where('sku', 'like', '%'.$search.'%')
                                ->orWhere('nama_produk', 'like', '%'.$search.'%')
                                ->orWhere('variasi', 'like', '%'.$search.'%');
                        })

                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('name', 'like', '%'.$search.'%');
                        })

                        ->orWhereHas('toko', function ($toko) use ($search) {
                            $toko->where('nama_toko', 'like', '%'.$search.'%');
                        });
                });
            })

            ->orderBy('tanggal', 'DESC')
            ->paginate($perPage)
            ->withQueryString();

        return view('gudang.retur', compact('pesanan'));
    }

    // Data Retur View Modal
    public function detailRetur($no_pesanan)
    {
        $pesanan = PesananPerProduk::with('pesanan.toko')
            ->where('no_pesanan', $no_pesanan)
            ->whereHas('pesanan', function ($query) {
                $query->where('tanggal', '>=', now()->subMonths(6));
            })
            ->get();

        if ($pesanan->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'pesanan sudah lebih dari 6 Bulan atau Pesanan tidak ditemukan.',
            ], 404);
        }

        $status = retur::where('per_produk_id', $pesanan[0]->id_per_produk)->first();
        if ($status) {
            return response()->json([
                'status' => false,
                'message' => 'Data sudah pernah di input.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $pesanan,
        ]);
    }

    public function returCreate(Request $request)
    {
        $request->validate([
            'produk' => ['required', 'array', 'min:1'],
            'produk.*.per_produk_id' => ['required', 'integer', 'exists:pesanan_per_produk,id_per_produk'],
            'produk.*.diterima' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:pengembalian,pengiriman gagal'],
        ]);

        DB::beginTransaction();
        try {
            // Mencari Nomor Pesanan
            $produkPertama = collect($request->produk)->first();
            $noPesanan = PesananPerProduk::where('id_per_produk', $produkPertama['per_produk_id'])->firstOrFail();

            $hppPesanan = [];

            // Looping Perproduk
            foreach ($request->produk as $produk) {
                $data = PesananPerProduk::where('id_per_produk', $produk['per_produk_id'])->firstOrFail();
                $jumlahItem = $data->jumlah;
                $diTerima = $produk['diterima'];

                if ($diTerima > $jumlahItem) {
                    throw new Exception(
                        "Jumlah barang diterima SKU {$data->sku} melebihi jumlah pesanan."
                    );
                }

                if ($diTerima < $jumlahItem) {
                    $hppPerItem = $data->hpp / $jumlahItem;
                    $itemRusak = $jumlahItem - $diTerima;
                    $totalHppRugi = $hppPerItem * $itemRusak;
                } else {
                    $totalHppRugi = 0;
                }

                PesananPerProduk::where('id_per_produk', $produk['per_produk_id'])->update([
                    'hpp' => $totalHppRugi,
                ]);

                $hppPesanan[] = $totalHppRugi;
                if ($diTerima > 0) {
                    $stok = stok_produk::where('sku_id', $data->sku)->first();
                    if (! $stok) {
                        throw new Exception(
                            "Stok untuk SKU {$data->sku} tidak ditemukan."
                        );
                    }

                    stok_produk::where('id', $stok->id)->increment('jumlah_tersedia', $diTerima);

                    mutasi_stok::create([
                        'stok_produk_id' => $stok->id,
                        'gudang_id' => Auth::id(),
                        'adm_penjualan_id' => null,
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $diTerima,
                        'keterangan' => 'Barang masuk dari retur',
                    ]);
                }

                retur::create([
                    'per_produk_id' => $produk['per_produk_id'],
                    'diterima' => $diTerima,
                ]);
            }

            $totalHppPesanan = array_sum($hppPesanan);
            Pesanan::where('no_pesanan', $noPesanan->no_pesanan)->update([
                'status' => $request->status,
                'total_hpp' => $totalHppPesanan,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data retur berhasil disimpan.',
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

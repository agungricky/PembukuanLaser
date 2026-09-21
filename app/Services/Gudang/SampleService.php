<?php

namespace App\Services\Gudang;

use App\Models\mutasi_stok;
use App\Models\Produk;
use App\Models\stok_produk;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SampleService
{
    public function barangsampel()
    {
        $produk = mutasi_stok::with('gudang', 'admin_penjualan', 'stok_produk.produk.kategori')
            ->where('jenis_mutasi', 'sampel')
            ->orderBy('created_at', 'DESC')
            ->get();
        $allproduk = Produk::with('kategori')->where('status', 'aktif')->get();
        $user = User::where('role', 'pegawai')->get();

        return view('gudang.sampel', compact('produk', 'allproduk', 'user'));
    }

    public function sampelcreate(Request $request)
    {
        $request->validate([
            'produk_id' => ['required', 'exists:produk,sku'],
            'nama_peminta' => ['required', 'exists:users,id'],
        ]);

        DB::beginTransaction();
        try {
            $stok = stok_produk::where('sku_id', $request->produk_id)->first();

            if (! $stok) {
                $stok = stok_produk::create([
                    'sku_id' => $request->produk_id,
                    'jumlah_tersedia' => '0',
                    'min_stok' => '5',
                ]);
            }

            if ($stok->jumlah_tersedia < $request->jumlah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Permintaan gagal Stok Tidak Cukup.',
                ], 500);
            }

            stok_produk::where('sku_id', $request->produk_id)->update([
                'jumlah_tersedia' => $stok->jumlah_tersedia - $request->jumlah,
            ]);

            mutasi_stok::create([
                'stok_produk_id' => $stok->id,
                'gudang_id' => auth()->id(),
                'adm_penjualan_id' => $request->nama_peminta,
                'jenis_mutasi' => 'sampel',
                'jumlah' => $request->jumlah,
                'keterangan' => 'Permintaan sampel',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Permintaan sampel berhasil dibuat.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

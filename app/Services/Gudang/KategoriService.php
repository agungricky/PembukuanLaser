<?php

namespace App\Services\Gudang;

use App\Models\kategori;
use App\Models\Produk;

class KategoriService
{
    public function kategori()
    {
        $kategori = kategori::with('produk')->get();
        foreach ($kategori as $item) {
            $item->jumlah_produk = $item->produk->count();
            $item->produk_aktif = $item->produk->where('status', 'aktif')->count();
            $item->produk_nonaktif = $item->produk->where('status', 'nonaktif')->count();
        }

        return view('gudang.kategori', compact('kategori'));
    }

    public function kategorishow(string $id)
    {
        $kategori = kategori::where('id', $id)->first();
        $data = Produk::where('kategori_id', $id)->get();

        return response()->json([
            'data' => $data,
            'kategori' => $kategori,
        ]);
    }
}

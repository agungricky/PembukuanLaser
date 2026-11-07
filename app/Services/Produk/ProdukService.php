<?php

namespace App\Services\Produk;

use App\Models\Produk;

class ProdukService
{
    public function produk($kategori)
    {
        $produk = Produk::where('kategori_id', $kategori)
            ->where('status', 'aktif')
            ->select('nama_produk', 'sku', 'variasi', 'hpp')
            ->orderBy('nama_produk')
            ->get()
            ->groupBy('nama_produk')
            ->map(function ($items, $namaProduk) {
                return [
                    'nama_produk' => $namaProduk,
                    'variasi' => $items->map(function ($item) {
                        return [
                            'sku' => $item->sku,
                            'nama' => $item->variasi,
                            'hpp' => (float) $item->hpp,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return $produk;
    }
}

<?php

namespace App\Services\Produk;

use App\Models\Produk;
use App\Models\stok_produk;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SkuServices
{
    public function produk(Request $request)
    {
        if (is_string($request->input('search'))) {
            $request->merge([
                'search' => [
                    'value' => $request->input('search'),
                    'regex' => false,
                ],
            ]);
        }

        $produk = Produk::query()
            ->with('kategori', 'stok_produk');

        if ($request->filled('kategori_id')) {
            $produk->where('kategori_id', $request->kategori_id);
        }

        return DataTables::eloquent($produk)
            ->filterColumn('sku', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('sku', 'like', "%{$keyword}%")
                        ->orWhere('nama_produk', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('stok', function ($query, $order) {
                $query->orderBy(
                    stok_produk::select('jumlah_tersedia')
                        ->whereColumn('stok_produks.sku_id', 'produk.sku')
                        ->limit(1),
                    $order
                );
            })
            ->addIndexColumn()
            ->addColumn('stok', function ($item) {
                return $item->stok_produk->jumlah_tersedia ?? 0;
            })
            ->addColumn('kategori_nama', function ($item) {
                return $item->kategori->nama_kategori ?? '';
            })
            ->toJson();
    }
}

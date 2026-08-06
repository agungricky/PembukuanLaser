<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\stok_produk;
use Illuminate\Http\Request;

class stokProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $produk = Produk::with('stok_produk')->get();

        return view('stok.stokproduk', compact('produk'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $produk = Produk::with('stok_produk')->findOrFail($id);

        return response()->json($produk);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function tambahstok(Request $request, string $id)
    {
        $request->validate([
            'sku' => 'required|exists:produk,sku',
            'jumlah' => 'required|integer|min:1',
        ]);

        $stokProduk = stok_produk::where('sku_id', $id)->first();
        if ($stokProduk) {
            $stokProduk->increment('jumlah_tersedia', $request->jumlah);
        } else {
            stok_produk::create([
                'sku_id' => $request->sku,
                'jumlah_tersedia' => $request->jumlah,
                'min_stok' => 5
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Stok berhasil ditambahkan.',
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'sku_edit' => 'required|exists:produk,sku',
            'stok_tersedia_edit' => 'required|integer|min:0',
        ]);

        $stokProduk = stok_produk::where('sku_id', $id)->first();
        if ($stokProduk) {
            stok_produk::where('sku_id', $id)->update([
                'jumlah_tersedia' => $request->stok_tersedia_edit,
            ]);
        } else {
            stok_produk::create([
                'sku_id' => $request->sku_edit,
                'jumlah_tersedia' => $request->stok_tersedia_edit,
                'min_stok' => 5
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Stok berhasil ditambahkan.',
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

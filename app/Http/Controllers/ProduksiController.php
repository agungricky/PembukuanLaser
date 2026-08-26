<?php

namespace App\Http\Controllers;

use App\Models\PesananPerProduk;
use App\Models\stok_produk;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $custom = PesananPerProduk::where('sku', 'LIKE', '%C')
            ->where('produksi', false)
            ->sum('jumlah');

        $stok = stok_produk::where('jumlah_tersedia', '<', '5')->count();

        $Card = [
            'custom' => $custom,
            'stok' => $stok
        ];

        return view('produksi.index', compact('Card'));
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

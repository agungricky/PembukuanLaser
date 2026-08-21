<?php

namespace App\Http\Controllers;

use App\Models\produkCustom;
use Illuminate\Http\Request;

class produkcustomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $produk = produkCustom::with('produk.kategori')->get();
        // dd($custom->toArray());
        return view('gudang.produk_custom', compact('produk'));
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
        // return response()->json($id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

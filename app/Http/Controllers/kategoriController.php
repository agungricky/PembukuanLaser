<?php

namespace App\Http\Controllers;

use App\Models\kategori;
use Illuminate\Http\Request;

class kategoriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategori = kategori::with('produk')->get();
        foreach ($kategori as $item) {
            $item->jumlah_produk = $item->produk->count();
            $item->produk_aktif = $item->produk->where('status', 'aktif')->count();
            $item->produk_nonaktif = $item->produk->where('status', 'nonaktif')->count();
        }

        return view('master.kategori', compact('kategori'));
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
        $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                'unique:kategoris,nama_kategori',
            ],
        ]);

        kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Kategori berhasil ditambahkan.',
        ]);
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
    public function edit($id)
    {
        $kategori = kategori::findOrFail($id);

        return response()->json([
            'id' => $kategori->id,
            'nama_kategori' => $kategori->nama_kategori,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
        ]);

        $kategori = kategori::findOrFail($id);
        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Kategori berhasil diperbarui.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $kategori = kategori::findOrFail($id);
        $kategori->delete();
        return response()->json([
            'status' => true,
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}

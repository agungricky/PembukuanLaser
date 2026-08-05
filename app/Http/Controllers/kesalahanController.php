<?php

namespace App\Http\Controllers;

use App\Models\Kesalahan;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class kesalahanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        // $pesanan = Pesanan::where('no_pesanan', $request->no_pesanan)->first();
        return response()->json($request->all());

        $request->validate([
            'no_pesanan'   => 'required|exists:pesanan,no_pesanan',
            'idKesalahan'  => 'required',
            'notes'        => 'nullable',
            'pencairan' => 'required',
        ]);

        try {
            Kesalahan::create([
                'no_pesanan'    => $request->no_pesanan,
                'kesalahan_id'  => $request->idKesalahan,
            ]);

            $pencairan = $request->pencairan;

            Pesanan::where('no_pesanan', $request->no_pesanan)
                ->update([
                    'status_cek' => 0,
                    'notes' => $request->notes,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan.',
            ], 500);
        }
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

<?php

namespace App\Http\Controllers;

use App\Models\mutasi_stok;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use App\Models\stok_produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProduksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ALERT
        $produk = Produk::where('nama_produk', '!=', null)->get();
        $stok = stok_produk::all();
        $alert = $produk->count() - $stok->count();

        // Produk Custom
        $custom = PesananPerProduk::where('custom', true)->sum('jumlah');

        // Produk Menipis
        $menipis = Produk::with('stok_produk')
            ->whereNotNull('nama_produk')
            ->whereHas('stok_produk', function ($query) {
                $query->where('jumlah_tersedia', '>', 5);
            })->get();
        $menipis = $produk->count() - $menipis->count();

        // Produk Terlaris
        $mutasi = mutasi_stok::with('stok_produk')->where('jenis_mutasi', 'keluar')->get();
        $terlaris = [];
        $terlaris = $mutasi
            ->groupBy('stok_produk.sku_id')
            ->map(function ($items, $sku) {
                return [
                    'sku' => $sku,
                    'jumlah' => $items->sum('jumlah'),
                ];
            })
            ->where('jumlah', '>=', 1000)
            ->values();
        $terlaris = $terlaris->count();
   
        // Produksi Hari ini
        // $dataLogin = Auth::user();
        // $bukanCustom = PesananPerProduk::whereDate('updated_at', now()->toDateString())
        //     ->where('produksi_id', $dataLogin->id)
        //     ->get();
        // $produkCustom = PesananPerProduk::whereDate('updated_at', now()->toDateString())
        //     ->where('produksi_id', $dataLogin->id)
        //     ->where('status_pesanan', "1")
        //     ->get();
        // $produksiNow = $bukanCustom->sum('jumlah') + $produkCustom->sum('jumlah');

        $Card = [
            'alert' => $alert,
            'custom' => $custom,
            'menipis' => $menipis,
            'terlaris' => $terlaris,
            // 'produksi' => $produksiNow
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

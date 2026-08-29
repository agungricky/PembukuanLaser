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
    private function card()
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
        $mutasi = mutasi_stok::with('stok_produk')
            ->where('jenis_mutasi', 'keluar')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

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
        $dataLogin = Auth::user();
        $produksiNow = mutasi_stok::where('produksi_id', $dataLogin->id)
            ->whereDate('created_at', now()->toDateString())
            ->get();
        $produksiNow = $produksiNow->sum('jumlah');

        return [
            'alert' => $alert,
            'custom' => $custom,
            'menipis' => $menipis,
            'terlaris' => $terlaris,
            'produksi' => $produksiNow,
        ];
    }

    public function index()
    {
        $Card = $this->card();

        $produkTerlaris = mutasi_stok::with('stok_produk')
            ->where('jenis_mutasi', 'keluar')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get()
            ->groupBy('stok_produk_id')
            ->map(function ($items) {
                return [
                    'sku' => $items->first()->stok_produk->sku_id,
                    'jumlah' => $items->sum('jumlah'),
                    'stok' => $items->first()->stok_produk->jumlah_tersedia,
                ];
            })
            ->take(30)
            ->values()->toArray();

        return view('produksi.index', compact('Card', 'produkTerlaris'));
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
    public function show(string $produksi)
    {
        return view('produksi.pesanan', compact('produksi'));
    }

    public function showpesanan($id)
    {
        $pesanan = PesananPerProduk::with([
            'pesanan' => function ($query) {
                $query->where('status', 'proses');
            },
        ])
            ->whereHas('pesanan', function ($query) {
                $query->where('status', 'proses');
            })
            ->where('custom', false)
            ->where('status_pesanan', false)
            ->where('status_produksi', false)
            ->whereNull('mutasi_stok_id')
            ->take(50)
            ->get();

        dd($pesanan->toArray());
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

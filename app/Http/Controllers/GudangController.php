<?php

namespace App\Http\Controllers;

use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\stok_produk;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GudangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function gudang()
    {
        $produk = Produk::whereNotNull(['nama_produk', 'variasi'])->get();
        $allStok_aman = $produk->count() * 5;
        $stokTersedia = stok_produk::sum('jumlah_tersedia');

        $tanggalHariIni = Carbon::now('Asia/Jakarta')->toDateString();
        $mutasi_masuk = mutasi_stok::where('jenis_mutasi', 'masuk')
            ->whereDate('created_at', $tanggalHariIni)
            ->sum('jumlah');
        $mutasi_keluar = mutasi_stok::where('jenis_mutasi', 'keluar')
            ->whereDate('created_at', $tanggalHariIni)
            ->sum('jumlah');
        $banyakMutasi = mutasi_stok::whereDate('created_at', $tanggalHariIni)->count();
        $stokAman = stok_produk::where('jumlah_tersedia', '>', 5)->count();
        $stokMenipis = stok_produk::where('jumlah_tersedia', '<=', 5)->count();
        $stokKritis = stok_produk::where('jumlah_tersedia', '<', 5)->count();
        $stokHabis = stok_produk::where('jumlah_tersedia', '<=', 0)->count();

        return view('gudang.Dashboard', [
            'Card' => [
                'allStok_aman' => $allStok_aman,
                'stokTersedia' => $stokTersedia,
                'mutasiMasuk' => $mutasi_masuk,
                'mutasiKeluar' => $mutasi_keluar,
                'banyakMutasi' => $banyakMutasi,
                'stokAman' => $stokAman,
                'stokMenipis' => $stokMenipis,
                'stokKritis' => $stokKritis,
                'stokHabis' => $stokHabis,
            ],
        ]);
    }

    public function gudanginventory($filter)
    {
        $pesanan = Pesanan::with('pesanan_per_produk.produk')
            ->where('status', 'proses')
            ->get();

        $kebutuhan = [];
        foreach ($pesanan as $value) {
            foreach ($value->pesanan_per_produk as $item) {
                $sku = $item->sku;
                $jumlah = $item->jumlah;

                if (isset($kebutuhan[$sku])) {
                    $kebutuhan[$sku] += $jumlah;
                } else {
                    $kebutuhan[$sku] = $jumlah;
                }
            }
        }

        $kebutuhanProduk = [];
        foreach ($kebutuhan as $sku => $jumlah) {
            $produk = Produk::with('stok_produk')->where('sku', $sku)->first();
            $kebutuhanProduk[] = [
                'produk' => $produk,
                'kebutuhan' => $jumlah,
            ];
        }

        $perluDisiapkan = count($kebutuhanProduk);

        return response()->json($kebutuhanProduk);

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

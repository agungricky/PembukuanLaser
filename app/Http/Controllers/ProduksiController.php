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

    public function showreguler(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim($request->input('search.value', ''));

        /*
        |--------------------------------------------------------------------------
        | QUERY DASAR
        |--------------------------------------------------------------------------
        */
        $query = PesananPerProduk::with('pesanan')
            ->where('custom', 0)
            ->where('status_pesanan', '0')
            ->where('status_produksi', false)
            ->whereHas('pesanan', function ($query) {
                $query->where('status', 'proses');
            });

        /*
        |--------------------------------------------------------------------------
        | TOTAL SKU (Melakukan filtering total sku unik)
        |--------------------------------------------------------------------------
        */
        $recordsTotal = (clone $query)
            ->distinct()
            ->count('sku');

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhereIn('sku', function ($subQuery) use ($search) {
                        $subQuery->select('sku')
                            ->from('produk')
                            ->where('nama_produk', 'like', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL SETELAH SEARCH
        |--------------------------------------------------------------------------
        */
        $recordsFiltered = (clone $query)
            ->distinct()
            ->count('sku');

        /*
        |--------------------------------------------------------------------------
        | SKU HALAMAN DATATABLES
        |--------------------------------------------------------------------------
        */
        $skuList = (clone $query)
            ->select('sku')
            ->groupBy('sku')
            ->orderBy('sku')
            ->skip($start)
            ->take($length)
            ->pluck('sku');

        /*
        |--------------------------------------------------------------------------
        | TOTAL PESANAN PER SKU
        |--------------------------------------------------------------------------
        | Database langsung COUNT + SUM agar lebih ringan
        */
        $items = PesananPerProduk::query()
            ->whereIn('sku', $skuList)
            ->where('custom', 0)
            ->where('status_pesanan', '0')
            ->where('status_produksi', false)
            ->whereNull('mutasi_stok_id')
            ->whereHas('pesanan', function ($query) {
                $query->where('status', 'proses');
            })
            ->select('sku')
            ->selectRaw('COUNT(*) AS jumlah_pesanan')
            ->selectRaw('COALESCE(SUM(jumlah), 0) AS total_pesanan')
            ->groupBy('sku')
            ->get()
            ->keyBy('sku');

        /*
        |--------------------------------------------------------------------------
        | PRODUK + STOK
        |--------------------------------------------------------------------------
        */
        $produk = Produk::with('stok_produk')
            ->whereIn('sku', $skuList)
            ->get()
            ->keyBy('sku');

        /*
        |--------------------------------------------------------------------------
        | ID STOK PRODUK
        |--------------------------------------------------------------------------
        */
        $stokProdukIds = $produk
            ->pluck('stok_produk.id')
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | TOTAL MUTASI KELUAR 7 HARI
        |--------------------------------------------------------------------------
        */
        $mutasiKeluar = mutasi_stok::query()
            ->whereIn('stok_produk_id', $stokProdukIds)
            ->where('jenis_mutasi', 'keluar')
            ->where('created_at', '>=', now()->subDays(7))
            ->select('stok_produk_id')
            ->selectRaw('COALESCE(SUM(jumlah), 0) AS total')
            ->groupBy('stok_produk_id')
            ->pluck('total', 'stok_produk_id');

        /*
        |--------------------------------------------------------------------------
        | DATA RESPONSE
        |--------------------------------------------------------------------------
        */
        $data = $skuList->map(function ($sku) use (
            $items,
            $produk,
            $mutasiKeluar
        ) {
            $item = $items->get($sku);
            $produkData = $produk->get($sku);
            $stokProduk = $produkData?->stok_produk;

            /*
            |--------------------------------------------------------------
            | PESANAN AKTIF
            |--------------------------------------------------------------
            */
            $pesananMasuk = (int) ($item?->total_pesanan ?? 0);

            /*
            |--------------------------------------------------------------
            | STOK TERSEDIA
            |--------------------------------------------------------------
            */
            $stok = (int) ($stokProduk?->jumlah_tersedia ?? 0);

            /*
            |--------------------------------------------------------------
            | KEBUTUHAN STOK BERDASARKAN 7 HARI TERAKHIR
            |--------------------------------------------------------------
            */
            $mutasi7Hari = $stokProduk ? (int) ($mutasiKeluar->get($stokProduk->id) ?? 0) : 0;
            $kekuranganStok = max($pesananMasuk - $stok, 0);

            /*
            |--------------------------------------------------------------
            | KEBUTUHAN PRODUKSI
            |--------------------------------------------------------------
            */
            $kebutuhanProduksi = $mutasi7Hari + $kekuranganStok;
            return [
                'sku' => $sku,
                'nama_produk' => $produkData?->nama_produk ?? '-',
                'variasi' => $produkData?->variasi ?? '-',
                'jumlah_pesanan' => (int) ($item?->jumlah_pesanan ?? 0),
                'pesanan_masuk' => $pesananMasuk,
                'stok' => $stok,
                'mutasi_terakhir' => $mutasi7Hari,
                'kekurangan_stok' => $kekuranganStok,
                'kebutuhan_produksi' => $kebutuhanProduksi,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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

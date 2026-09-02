<?php

namespace App\Http\Controllers;

use App\Exports\indexRegulerExport;
use App\Exports\produkRegulerExport;
use App\Models\mutasi_stok;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use App\Models\stok_produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProduksiController extends Controller
{
    // ================================ //
    // ============ DASHBOARD ========= //
    // ================================ //
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

    //                                  //
    //                                  //
    //                                  //
    //                                  //
    //                                  //
    // ================================ //
    // ======== PRODUK REGULER ======== //
    // ================================ //
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
        | 1. AMBIL DATA 3 BULAN TERAKHIR SEKALI SAJA
        |--------------------------------------------------------------------------
        */
        $dataAwal = PesananPerProduk::with([
            'pesanan',
        ])
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. FILTER DI MEMORY / COLLECTION
        |--------------------------------------------------------------------------
        */
        $dataFilter = $dataAwal
            ->where('custom', 0)
            ->where('status_pesanan', '0')
            ->where('status_produksi', false)
            ->whereNull('mutasi_stok_id')
            ->filter(function ($item) {
                return $item->pesanan?->status === 'proses';
            });

        /*
        |--------------------------------------------------------------------------
        | TOTAL SKU
        |--------------------------------------------------------------------------
        */
        $recordsTotal = $dataFilter
            ->pluck('sku')
            ->unique()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */
        if ($search !== '') {

            $produkSearch = Produk::where('nama_produk', 'like', "%{$search}%")
                ->pluck('sku');

            $dataFilter = $dataFilter->filter(function ($item) use (
                $search,
                $produkSearch
            ) {
                return str_contains(
                    strtolower($item->sku ?? ''),
                    strtolower($search)
                )
                || $produkSearch->contains($item->sku);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL SETELAH SEARCH
        |--------------------------------------------------------------------------
        */
        $recordsFiltered = $dataFilter
            ->pluck('sku')
            ->unique()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | GROUP BERDASARKAN SKU
        |--------------------------------------------------------------------------
        */
        $grouped = $dataFilter
            ->groupBy('sku')
            ->map(function ($items, $sku) {

                return [
                    'sku' => $sku,
                    'jumlah_pesanan' => $items->count(),
                    'total_pesanan' => $items->sum('jumlah'),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | PAGINATION SETELAH DATA SUDAH DIFILTER
        |--------------------------------------------------------------------------
        */
        $items = $grouped
            ->slice($start, $length)
            ->values();

        $skuList = $items->pluck('sku');

        /*
        |--------------------------------------------------------------------------
        | PRODUK
        |--------------------------------------------------------------------------
        */
        $produk = Produk::with('stok_produk')
            ->whereIn('sku', $skuList)
            ->get()
            ->keyBy('sku');

        /*
        |--------------------------------------------------------------------------
        | ID STOK
        |--------------------------------------------------------------------------
        */
        $stokProdukIds = $produk
            ->pluck('stok_produk.id')
            ->filter();

        /*
        |--------------------------------------------------------------------------
        | MUTASI 7 HARI
        |--------------------------------------------------------------------------
        */
        $mutasiKeluar = mutasi_stok::whereIn(
            'stok_produk_id',
            $stokProdukIds
        )
            ->where('jenis_mutasi', 'keluar')
            ->where('created_at', '>=', now()->subDays(7))
            ->select('stok_produk_id')
            ->selectRaw('SUM(jumlah) AS total')
            ->groupBy('stok_produk_id')
            ->pluck('total', 'stok_produk_id');

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        $data = $items->map(function ($item) use (
            $produk,
            $mutasiKeluar
        ) {

            $sku = $item['sku'];
            $produkData = $produk->get($sku);
            $stokProduk = $produkData?->stok_produk;
            $pesananMasuk = (int) $item['total_pesanan'];
            $stok = (int) ($stokProduk?->jumlah_tersedia ?? 0);
            $mutasi7Hari = $stokProduk
                ? (int) ($mutasiKeluar->get($stokProduk->id) ?? 0)
                : 0;
            $kekuranganStok = max(
                $pesananMasuk - $stok,
                0
            );

            $kebutuhanProduksi =
                $mutasi7Hari +
                $kekuranganStok;

            return [
                'sku' => $sku,
                'nama_produk' => $produkData?->nama_produk ?? '-',
                'variasi' => $produkData?->variasi ?? '-',
                'jumlah_pesanan' => $item['jumlah_pesanan'],
                'pesanan_masuk' => $pesananMasuk,
                'stok' => $stok,
                'mutasi_terakhir' => $mutasi7Hari,
                'kekurangan_stok' => $kekuranganStok,
                'kebutuhan_produksi' => $kebutuhanProduksi,
            ];
        });

        $data = $data
            ->filter(function ($item) {
                return ($item['kebutuhan_produksi'] ?? 0) > 0;
            })
            ->sortByDesc(function ($item) {
                return $item['kebutuhan_produksi'] ?? 0;
            })
            ->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function exportreguler()
    {
        return Excel::download(
            new indexRegulerExport(),
            'produk-reguler-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    //                                  //
    //                                  //
    //                                  //
    //                                  //
    //                                  //
    // ================================ //
    // ======== PRODUK REGULER ======== //
    // ================================ //

    public function stokmenipis()
    {
        return view('produksi.stok_menipis');
    }

    public function stokdata()
    {

        $data = Produk::with([
            'stok_produk.mutasi_stok' => function ($query) {
                $query->where('jenis_mutasi', 'keluar')
                    ->where('created_at', '>=', now()->subDays(7));
            },
        ])
            ->where(function ($query) {
                $query->whereDoesntHave('stok_produk')
                    ->orWhereHas('stok_produk', function ($query) {
                        $query->where('jumlah_tersedia', '<', 5);
                    });
            })
            ->get()
            ->map(function ($produk) {
                $totalKeluar = $produk->stok_produk
                    ? $produk->stok_produk->mutasi_stok->sum('jumlah')
                    : 0;

                $produk->total_keluar = (int) $totalKeluar;
                if ($produk->stok_produk) {
                    unset($produk->stok_produk->mutasi_stok);
                }

                return $produk;
            })
            ->sortByDesc('total_keluar')
            ->values();

        return response()->json([
            'data' => $data,
        ]);
    }

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

<?php

namespace App\Http\Controllers;

use App\Models\kategori;
use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\stok_produk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $stokMenipis = stok_produk::whereBetween('jumlah_tersedia', [3, 5])->count();
        $stokKritis = stok_produk::whereBetween('jumlah_tersedia', [1, 2])->count();
        $stokHabis = stok_produk::where('jumlah_tersedia', '=', 0)->count();
        $terlaris = mutasi_stok::with('stok_produk.produk')
            ->where('jenis_mutasi', 'keluar')
            ->get()
            ->groupBy('stok_produk_id')
            ->map(function ($items) {
                $mutasi = $items->first();
                $stok = $mutasi->stok_produk;
                $produk = $stok?->produk;

                if (! $produk) {
                    return null;
                }

                return (object) [
                    'sku' => $produk->sku,
                    'nama_produk' => $produk->nama_produk,
                    'variasi' => $produk->variasi,
                    'jumlah' => $items->sum('jumlah'),
                ];
            })
            ->filter()
            ->sortByDesc('jumlah')
            ->values()
            ->take(10);

        $aktivitas = mutasi_stok::with(['stok_produk.produk.kategori'])
            ->latest()
            ->take(10)
            ->orderBy('created_at', 'DESC')
            ->get();

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
            'Produk' => [
                'terlaris' => $terlaris,
            ],
            'Aktivitas' => [
                'aktivitas' => $aktivitas,
            ],
        ]);
    }

    public function gudanginventory()
    {
        $pesanan = Pesanan::with('pesanan_per_produk.produk')
            ->where('status', 'proses')
            ->get();

        $kebutuhan = [];
        foreach ($pesanan as $value) {
            foreach ($value->pesanan_per_produk as $item) {
                $sku = $item->sku;
                $jumlah = $item->jumlah;

                if ($item->status_pesanan == 0) {
                    if (isset($kebutuhan[$sku])) {
                        $kebutuhan[$sku] += $jumlah;
                    } else {
                        $kebutuhan[$sku] = $jumlah;
                    }
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

        return response()->json($kebutuhanProduk);

    }

    public function allindex()
    {
        return view('gudang.allpesanan');
    }

    public function allpesanan($filter)
    {
        if ($filter === 'siapkan') {
            $pesanan = Pesanan::with('pesanan_per_produk.produk')
                ->where('status', 'proses')
                ->get();

            $kebutuhan = [];
            foreach ($pesanan as $value) {
                foreach ($value->pesanan_per_produk as $item) {
                    $sku = $item->sku;
                    $jumlah = $item->jumlah;

                    if ($item->status_pesanan == 0) {
                        if (isset($kebutuhan[$sku])) {
                            $kebutuhan[$sku] += $jumlah;
                        } else {
                            $kebutuhan[$sku] = $jumlah;
                        }
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

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'siap') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk')
                ->where('jenis_mutasi', 'siap')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'diambil') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk')
                ->where('jenis_mutasi', 'keluar')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('gudang.transaksi', compact('id'));
    }

    public function showdata($filter)
    {
        if ($filter === 'siapkan') {
            $pesanan = Pesanan::with('pesanan_per_produk.produk')
                ->where('status', 'proses')
                ->get();

            $kebutuhan = [];
            foreach ($pesanan as $value) {
                foreach ($value->pesanan_per_produk as $item) {
                    $sku = $item->sku;
                    $jumlah = $item->jumlah;

                    if ($item->status_pesanan == 0) {
                        if (isset($kebutuhan[$sku])) {
                            $kebutuhan[$sku] += $jumlah;
                        } else {
                            $kebutuhan[$sku] = $jumlah;
                        }
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

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'siap') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk')
                ->where('jenis_mutasi', 'siap')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'diambil') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk')
                ->where('jenis_mutasi', 'keluar')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|array',
            'sku.*' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $pesanan = Pesanan::with('pesanan_per_produk.produk.stok_produk')
                ->where('status', 'proses')
                ->get();

            $kebutuhan = [];
            foreach ($pesanan as $value) {
                foreach ($value->pesanan_per_produk as $item) {
                    // Hanya SKU yang dipilih + belum diproses

                    if ($item->status_pesanan == 0 && in_array($item->sku, $request->sku)) {

                        $sku = $item->sku;
                        $jumlah = $item->jumlah;

                        // =========================
                        // KUMPULKAN TOTAL PER SKU
                        // =========================
                        if (isset($kebutuhan[$sku])) {
                            $kebutuhan[$sku]['jumlah'] += $jumlah;
                        } else {
                            $kebutuhan[$sku] = [
                                'jumlah' => $jumlah,
                                'stok_produk_id' => $item->produk->stok_produk->id,
                            ];
                        }

                        // =========================
                        // UPDATE STATUS PESANAN
                        // =========================
                        $item->status_pesanan = '1';
                        $item->save();
                    }
                }
            }

            // =========================
            // BUAT MUTASI STOK
            // =========================
            foreach ($kebutuhan as $sku => $data) {

                mutasi_stok::create([
                    'stok_produk_id' => $data['stok_produk_id'],
                    'user_id' => auth()->id(),
                    'jenis_mutasi' => 'siap',
                    'jumlah' => $data['jumlah'],
                    'keterangan' => '',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil disiapkan',
                'data' => $kebutuhan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        DB::beginTransaction();
        try {

            foreach ($request->sku as $id) {
                $data = mutasi_stok::find($id);

                if ($data) {
                    $data->jenis_mutasi = 'keluar';
                    $data->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function produk()
    {
        $produk = Produk::with('stok_produk', 'kategori')->get();

        return view('gudang.produk', compact('produk'));
    }

    public function produkShow($id)
    {
        $produk = Produk::with('stok_produk', 'kategori')
            ->where('sku', $id)
            ->first();

        return response()->json($produk);
    }

    public function updatestok(Request $request)
    {
        $request->validate([
            'sku_id' => 'required',
            'btn' => 'required|in:add,edit',
        ]);

        DB::beginTransaction();
        try {
            $stok = stok_produk::where('sku_id', $request->sku_id)->first();

            if ($stok && $request->btn == 'add') {
                stok_produk::where('sku_id', $request->sku_id)->update([
                    'jumlah_tersedia' => $stok->jumlah_tersedia + $request->jumlah_add,
                ]);

                mutasi_stok::create([
                    'stok_produk_id' => $stok->id,
                    'user_id' => auth()->id(),
                    'jenis_mutasi' => 'masuk',
                    'jumlah' => $request->jumlah_add,
                    'keterangan' => null,
                ]);
            } elseif ($stok && $request->btn == 'edit') {
                stok_produk::where('sku_id', $request->sku_id)->update([
                    'jumlah_tersedia' => $request->jumlah_edit,
                ]);

                mutasi_stok::create([
                    'stok_produk_id' => $stok->id,
                    'user_id' => auth()->id(),
                    'jenis_mutasi' => 'edit',
                    'jumlah' => $request->jumlah_edit,
                    'keterangan' => $request->keterangan
                    .' (stok awal '
                    .$stok->jumlah_tersedia
                    .', stok akhir '
                    .$request->jumlah_edit
                    .')',
                ]);
            } else {
                if ($request->btn == 'add') {
                    $stokProduk = stok_produk::create([
                        'sku_id' => $request->sku_id,
                        'jumlah_tersedia' => $request->jumlah_add,
                        'min_stok' => 5,
                    ]);

                    mutasi_stok::create([
                        'stok_produk_id' => $stokProduk->id,
                        'user_id' => auth()->id(),
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $request->jumlah_add,
                        'keterangan' => null,
                    ]);
                } elseif ($request->btn == 'edit') {
                    $stokProduk = stok_produk::create([
                        'sku_id' => $request->sku_id,
                        'jumlah_tersedia' => $request->jumlah_edit,
                        'min_stok' => 5,
                    ]);

                    mutasi_stok::create([
                        'stok_produk_id' => $stokProduk->id,
                        'user_id' => auth()->id(),
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $request->jumlah_edit,
                        'keterangan' => null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil di Update.',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan stok: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function kategori()
    {
        $kategori = kategori::with('produk')->get();
        foreach ($kategori as $item) {
            $item->jumlah_produk = $item->produk->count();
            $item->produk_aktif = $item->produk->where('status', 'aktif')->count();
            $item->produk_nonaktif = $item->produk->where('status', 'nonaktif')->count();
        }

        return view('gudang.kategori', compact('kategori'));
    }

    public function kategorishow(string $id)
    {
        $kategori = kategori::where('id', $id)->first();
        $data = Produk::where('kategori_id', $id)->get();

        return response()->json([
            'data' => $data,
            'kategori' => $kategori,
        ]);
    }

    public function riwayataktivitas()
    {
        return view('gudang.riwayat_aktivitas');
    }

    public function riwayatAktivitasData(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Query awal
        |--------------------------------------------------------------------------
        */
        $query = mutasi_stok::with([
            'stok_produk.produk.kategori',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Total seluruh data
        |--------------------------------------------------------------------------
        */
        $totalData = mutasi_stok::count();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                // dari tabel mutasi_stok
                $q->where('jenis_mutasi', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('stok_produk.produk', function ($q) use ($search) {
                        $q->where('sku', 'like', "%{$search}%")
                            ->orWhere('nama_produk', 'like', "%{$search}%")
                            ->orWhere('variasi', 'like', "%{$search}%");

                    })
                    ->orWhereHas('stok_produk.produk.kategori', function ($q) use ($search) {

                        $q->where('nama_kategori', 'like', "%{$search}%");

                    });

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Total setelah search
        |--------------------------------------------------------------------------
        */

        $totalFiltered = $query->count();

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        |
        | Riwayat terbaru ditampilkan paling atas
        |
        */

        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        $orderDirection = $orderDirection === 'asc' ? 'asc' : 'desc';

        if ($orderColumn === 0) {
            $query->orderBy('id', $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination DataTables
        |--------------------------------------------------------------------------
        */

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        /*
        |--------------------------------------------------------------------------
        | Ambil data
        |--------------------------------------------------------------------------
        */

        $data = $query
            ->skip($start)
            ->take($length)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Format Data
        |--------------------------------------------------------------------------
        */

        $result = [];
        foreach ($data as $index => $item) {
            $produk = $item->stok_produk?->produk;
            if ($orderColumn === 0 && $orderDirection === 'desc') {
                $no = $totalFiltered - $start - $index;
            } else {
                $no = $start + $index + 1;
            }
            $result[] = [
                'no' => $no,
                'produk' => $produk?->nama_produk ?? '-',
                'sku' => $produk?->sku ?? '-',
                'variasi' => $produk?->variasi ?? '-',
                'kategori' => $produk?->kategori?->nama_kategori ?? '-',
                'hpp' => $produk?->hpp ?? 0,
                'jumlah' => $item->jumlah ?? 0,
                'jenis_mutasi' => $item->jenis_mutasi ?? '-',
                'keterangan' => $item->keterangan ?? '-',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Response DataTables
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $result,

        ]);
    }

    public function detailcard($card)
    {
        $query = stok_produk::with([
            'produk.kategori',
        ]);

        if ($card === 'aman') {
            $query->where('jumlah_tersedia', '>', 5);
        } elseif ($card === 'menipis') {
            $query->whereBetween('jumlah_tersedia', [3, 5]);
        } elseif ($card === 'kritis') {
            $query->whereBetween('jumlah_tersedia', [1, 2]);
        } elseif ($card === 'habis') {
            $query->where('jumlah_tersedia', '=', 0);
        } else {
            return response()->json([
                'message' => 'Filter tidak valid',
            ], 400);
        }
        $data = $query->orderBy('jumlah_tersedia', 'asc')->get();

        return response()->json($data);
    }
}

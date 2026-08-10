<?php

namespace App\Http\Controllers;

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
            'sku' => 'required',
            'jumlah' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $stok = stok_produk::where('sku_id', $request->sku)->first();
            if ($stok) {
                // Kalau SKU sudah ada, tambahkan stok
                $stok->jumlah_tersedia += $request->jumlah;
                $stok->save();

            } else {
                // Kalau SKU belum ada, buat stok baru
                stok_produk::create([
                    'sku_id' => $request->sku,
                    'jumlah_tersedia' => $request->jumlah,
                    'min_stok' => 5,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil ditambahkan.',
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     // $data = mutasi_stok::where()
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

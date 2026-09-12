<?php

namespace App\Http\Controllers;

use App\Exports\gudangStokExport;
use App\Imports\stokImport;
use App\Models\kategori;
use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use App\Models\ResiPage;
use App\Models\retur;
use App\Models\stok_produk;
use App\Models\User;
use App\Services\Gudang\DashboardService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Fpdi;

class GudangController extends Controller
{

    protected DashboardService $dashboardService;
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    // ==================================================//
    // ================== DASHBOARD =====================//
    // ==================================================//
    public function gudang()
    {
        return $this->dashboardService->gudang();
    }

    public function gudanginventory()
    {
        return $this->dashboardService->gudanginventory();
    }

    public function allindex()
    {
        return view('gudang.allpesanan');
    }

    public function allpesanan($filter)
    {
        return $this->dashboardService->allpesanan($filter);
    }

    public function detailcard($card)
    {
        return $this->dashboardService->detailcard($card);
    }

    public function detailpesanan($filter, $sku)
    {
        return $this->detailpesanan($filter, $sku);
    }

    // ==================================================//
    // ================== TRANSAKSI =====================//
    // ==================================================//
    public function show(string $id)
    {
        return view('gudang.transaksi', compact('id'));
    }

    public function showdata($filter)
    {
        if ($filter === 'siapkan') {
            $pesanan = Pesanan::where('status', 'proses')->pluck('no_pesanan');

            $pesananPerProduk = PesananPerProduk::with([
                'produk.stok_produk',
            ])
                ->whereIn('no_pesanan', $pesanan)
                ->where('custom', 0)
                ->where('status_pesanan', '0')
                ->get();

            $kebutuhanProduk = $pesananPerProduk
                ->groupBy('sku')
                ->map(function ($items, $sku) {
                    $produk = $items->first()->produk;

                    return [
                        'produk' => $produk,
                        'stok' => $produk?->stok_produk?->jumlah_tersedia ?? 0,
                        'kebutuhan' => $items->sum('jumlah'),
                    ];
                })
                ->sortByDesc(function ($item) {
                    return $item['stok'] >= $item['kebutuhan'];
                })
                ->values();

            return response()->json($kebutuhanProduk);
        } elseif ($filter === 'siap') {
            $kebutuhanProduk = mutasi_stok::with('stok_produk.produk', 'gudang', 'admin_penjualan')
                ->where('jenis_mutasi', 'siap')
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);

        } elseif ($filter === 'diambil') {
            $kebutuhanProduk = mutasi_stok::with(
                'stok_produk.produk',
                'gudang',
                'admin_penjualan'
            )
                ->where('jenis_mutasi', 'keluar')
                ->where('updated_at', '>=', now()->subMonths(3))
                ->orderBy('updated_at', 'DESC')
                ->get();

            return response()->json($kebutuhanProduk);
        }
    }

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
                            $kebutuhan[$sku]['perproduk_ids'][] = $item->id_per_produk;
                        } else {
                            $kebutuhan[$sku] = [
                                'jumlah' => $jumlah,
                                'stok_produk_id' => $item->produk->stok_produk->id,
                                'perproduk_ids' => [$item->id_per_produk],
                            ];
                        }
                    }
                }
            }

            // =========================
            // BUAT MUTASI STOK
            // =========================
            foreach ($kebutuhan as $sku => $data) {
                $mutasi = mutasi_stok::create([
                    'stok_produk_id' => $data['stok_produk_id'],
                    'gudang_id' => auth()->id(),
                    'jenis_mutasi' => 'siap',
                    'jumlah' => $data['jumlah'],
                    'keterangan' => '',
                ]);

                // =========================
                // UPDATE PESANAN PER PRODUK
                // =========================
                PesananPerProduk::whereIn('id_per_produk', $data['perproduk_ids'])
                    ->update([
                        'status_pesanan' => '1',
                        'mutasi_stok_id' => $mutasi->id,
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil disiapkan',
                'data' => $kebutuhan,
            ]);
        } catch (Exception $e) {
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
                $stok = stok_produk::find($data->stok_produk_id);

                if ($data) {
                    $data->jenis_mutasi = 'keluar';
                    $data->adm_penjualan_id = $request->pengambil_barang;
                    $data->save();

                    stok_produk::where('id', $data->stok_produk_id)->update([
                        'jumlah_tersedia' => $stok->jumlah_tersedia - $data->jumlah,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateselesai(Request $request){
        $request->validate([
            'no_pesanan' => 'required',
        ]);

        PesananPerProduk::whereIn('no_pesanan', $request->no_pesanan)->update([
            'status_pesanan' => '1',
        ]);

        Pesanan::whereIn('no_pesanan', $request->no_pesanan)->update([
            'status' => 'kirim',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diupdate'
        ]);
    }

    protected function belumdiImport($noPesanan)
    {
        $resiPages = ResiPage::with('resi_imports')->whereIn('no_pesanan', $noPesanan)->get();
        $pesananDitemukan = $resiPages->pluck('no_pesanan')->unique();
        $pesananTidakDitemukan = collect($noPesanan)->diff($pesananDitemukan);
        $tidakDitemukan = Pesanan::join(
            'pesanan_per_produk',
            'pesanan.no_pesanan',
            '=',
            'pesanan_per_produk.no_pesanan'
        )
            ->whereIn('pesanan.no_pesanan', $pesananTidakDitemukan)
            ->get([
                'pesanan.no_pesanan',
                'pesanan.no_resi',
                'pesanan_per_produk.sku',
            ]);

        if ($pesananTidakDitemukan->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat Pesanan yang Resinya Belum di Import.',
                'tidak_ditemukan' => $tidakDitemukan->values(),
            ], 422);
        }
    }

    public function previewResi($token)
    {
        $folder = 'temp/resi/'.$token;

        if (! Storage::disk('local')->exists($folder)) {
            abort(404, 'Folder resi tidak ditemukan.');
        }

        $files = Storage::disk('local')->files($folder);

        if (empty($files)) {
            abort(404, 'PDF resi tidak ditemukan.');
        }

        $path = Storage::disk('local')->path(
            $files[0]
        );

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-resi.pdf"',
        ]);
    }

    public function cetakResi(Request $request)
    {
        $request->validate([
            'sku' => ['required', 'array', 'min:1'],
            'sku.*' => ['required', 'string'],
        ]);

        $mutasi = mutasi_stok::whereIn('id', $request->sku)->get();

        $noPesanan = PesananPerProduk::whereIn(
            'mutasi_stok_id',
            $mutasi->pluck('id')
        )
            ->distinct()
            ->pluck('no_pesanan');

        $cekImport = $this->belumdiImport($noPesanan);

        if ($cekImport) {
            return $cekImport;
        }

        $resiPages = ResiPage::select(
            'id',
            'no_pesanan',
            'resi_import_id',
            'halaman',
            'urutan'
        )
            ->with([
                'resi_imports:id,path_file,nama_file',
            ])
            ->whereIn('no_pesanan', $noPesanan)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'no_pesanan' => $item->no_pesanan,
                    'resi_import_id' => $item->resi_import_id,
                    'halaman' => $item->halaman,
                    'urutan' => $item->urutan,
                    'path_file' => $item->resi_imports?->path_file,
                    'nama_file' => $item->resi_imports?->nama_file,
                ];
            });

        // BUAT TOKEN
        $token = (string) Str::uuid();

        // FOLDER TEMP
        $tempFolder = 'temp/resi/'.$token;

        Storage::disk('local')->makeDirectory($tempFolder);

        $hasilPotong = [];

        foreach ($resiPages as $item) {

            $sourcePath = Storage::disk('local')->path(
                $item['path_file']
            );

            $pdf = new Fpdi;

            // Baca PDF sumber
            $pageCount = $pdf->setSourceFile($sourcePath);

            // Cek halaman
            if (
                $item['halaman'] < 1 ||
                $item['halaman'] > $pageCount
            ) {
                throw new Exception(
                    'Halaman '.$item['halaman'].
                    ' tidak ditemukan pada file '.
                    $item['nama_file']
                );
            }

            // Ambil halaman
            $templateId = $pdf->importPage(
                $item['halaman']
            );

            $size = $pdf->getTemplateSize(
                $templateId
            );

            // Buat halaman baru
            $pdf->AddPage(
                $size['orientation'],
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate($templateId);

            // Nama file berdasarkan no pesanan
            $namaFile = $item['no_pesanan'].'.pdf';

            $relativePath = $tempFolder.'/'.$namaFile;

            $outputPath = Storage::disk('local')->path(
                $relativePath
            );

            // Simpan PDF
            $pdf->Output(
                'F',
                $outputPath
            );

            $hasilPotong[] = [
                'no_pesanan' => $item['no_pesanan'],
                'halaman' => $item['halaman'],
                'path' => $relativePath,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Resi berhasil diproses.',
            'preview_url' => route(
                'transaksi.preview-resi',
                [
                    'token' => $token,
                ]
            ),
        ]);
    }

    // ==================================================//
    // ============== BARANG SAMPEL =====================//
    // ==================================================//
    public function barangsampel()
    {
        $produk = mutasi_stok::with('gudang', 'admin_penjualan', 'stok_produk.produk.kategori')
            ->where('jenis_mutasi', 'sampel')
            ->orderBy('created_at', 'DESC')
            ->get();
        $allproduk = Produk::with('kategori')->where('status', 'aktif')->get();
        $user = User::where('role', 'pegawai')->get();

        return view('gudang.sampel', compact('produk', 'allproduk', 'user'));
    }

    public function sampelcreate(Request $request)
    {
        $request->validate([
            'produk_id' => ['required', 'exists:produk,sku'],
            'nama_peminta' => ['required', 'exists:users,id'],
        ]);

        DB::beginTransaction();
        try {
            $stok = stok_produk::where('sku_id', $request->produk_id)->first();

            if (! $stok) {
                $stok = stok_produk::create([
                    'sku_id' => $request->produk_id,
                    'jumlah_tersedia' => '0',
                    'min_stok' => '5',
                ]);
            }

            if ($stok->jumlah_tersedia < $request->jumlah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Permintaan gagal Stok Tidak Cukup.',
                ], 500);
            }

            stok_produk::where('sku_id', $request->produk_id)->update([
                'jumlah_tersedia' => $stok->jumlah_tersedia - $request->jumlah,
            ]);

            mutasi_stok::create([
                'stok_produk_id' => $stok->id,
                'gudang_id' => auth()->id(),
                'adm_penjualan_id' => $request->nama_peminta,
                'jenis_mutasi' => 'sampel',
                'jumlah' => $request->jumlah,
                'keterangan' => 'Permintaan sampel',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Permintaan sampel berhasil dibuat.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================================================//
    // =============== BARANG RETUR =====================//
    // ==================================================//
    public function barangretur(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 10;
        $pesanan = Pesanan::with([
            'pesanan_per_produk.retur',
            'user',
            'toko',
        ])->whereIn('status', [
            'pengembalian',
            'pengiriman gagal',
        ])->whereHas('pesanan_per_produk.retur')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {

                    $q->where('no_pesanan', 'like', '%'.$search.'%')
                        ->orWhere('nama_pembeli', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('no_resi', 'like', '%'.$search.'%')
                        ->orWhere('kurir', 'like', '%'.$search.'%')
                        ->orWhere('status', 'like', '%'.$search.'%')

                        ->orWhereHas('pesanan_per_produk', function ($produk) use ($search) {
                            $produk->where('sku', 'like', '%'.$search.'%')
                                ->orWhere('nama_produk', 'like', '%'.$search.'%')
                                ->orWhere('variasi', 'like', '%'.$search.'%');
                        })

                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('name', 'like', '%'.$search.'%');
                        })

                        ->orWhereHas('toko', function ($toko) use ($search) {
                            $toko->where('nama_toko', 'like', '%'.$search.'%');
                        });
                });
            })

            ->orderBy('tanggal', 'DESC')
            ->paginate($perPage)
            ->withQueryString();

        return view('gudang.retur', compact('pesanan'));
    }

    // Data Retur View Modal
    public function detailRetur($no_pesanan)
    {
        $pesanan = PesananPerProduk::with('pesanan.toko')
            ->where('no_pesanan', $no_pesanan)
            ->whereHas('pesanan', function ($query) {
                $query->where('tanggal', '>=', now()->subMonths(6));
            })
            ->get();

        if ($pesanan->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'pesanan sudah lebih dari 6 Bulan atau Pesanan tidak ditemukan.',
            ], 404);
        }

        $status = retur::where('per_produk_id', $pesanan[0]->id_per_produk)->first();
        if ($status) {
            return response()->json([
                'status' => false,
                'message' => 'Data sudah pernah di input.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $pesanan,
        ]);
    }

    public function returCreate(Request $request)
    {
        $request->validate([
            'produk' => ['required', 'array', 'min:1'],
            'produk.*.per_produk_id' => ['required', 'integer', 'exists:pesanan_per_produk,id_per_produk'],
            'produk.*.diterima' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:pengembalian,pengiriman gagal'],
        ]);

        DB::beginTransaction();
        try {
            // Mencari Nomor Pesanan
            $produkPertama = collect($request->produk)->first();
            $noPesanan = PesananPerProduk::where('id_per_produk', $produkPertama['per_produk_id'])->firstOrFail();

            $hppPesanan = [];

            // Looping Perproduk
            foreach ($request->produk as $produk) {
                $data = PesananPerProduk::where('id_per_produk', $produk['per_produk_id'])->firstOrFail();
                $jumlahItem = $data->jumlah;
                $diTerima = $produk['diterima'];

                if ($diTerima > $jumlahItem) {
                    throw new Exception(
                        "Jumlah barang diterima SKU {$data->sku} melebihi jumlah pesanan."
                    );
                }

                if ($diTerima < $jumlahItem) {
                    $hppPerItem = $data->hpp / $jumlahItem;
                    $itemRusak = $jumlahItem - $diTerima;
                    $totalHppRugi = $hppPerItem * $itemRusak;
                } else {
                    $totalHppRugi = 0;
                }

                PesananPerProduk::where('id_per_produk', $produk['per_produk_id'])->update([
                    'hpp' => $totalHppRugi,
                ]);

                $hppPesanan[] = $totalHppRugi;
                if ($diTerima > 0) {
                    $stok = stok_produk::where('sku_id', $data->sku)->first();
                    if (! $stok) {
                        throw new Exception(
                            "Stok untuk SKU {$data->sku} tidak ditemukan."
                        );
                    }

                    stok_produk::where('id', $stok->id)->increment('jumlah_tersedia', $diTerima);

                    mutasi_stok::create([
                        'stok_produk_id' => $stok->id,
                        'gudang_id' => Auth::id(),
                        'adm_penjualan_id' => null,
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $diTerima,
                        'keterangan' => 'Barang masuk dari retur',
                    ]);
                }

                retur::create([
                    'per_produk_id' => $produk['per_produk_id'],
                    'diterima' => $diTerima,
                ]);
            }

            $totalHppPesanan = array_sum($hppPesanan);
            Pesanan::where('no_pesanan', $noPesanan->no_pesanan)->update([
                'status' => $request->status,
                'total_hpp' => $totalHppPesanan,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data retur berhasil disimpan.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================================================//
    // ============== PRODUK CUSTOM =====================//
    // ==================================================//
    public function produkcustom()
    {
        $pesanan_perproduk = PesananPerProduk::with('pesanan')
            ->where('custom', 1)
            ->whereHas('pesanan')
            ->get();

        $data = $pesanan_perproduk
            ->groupBy(function ($item) {
                $tanggal = Carbon::parse($item->pesanan->tanggal);

                return $item->sku.'-'.$tanggal->format('Y-m');
            })
            ->map(function ($items) {
                $firstItem = $items->first();
                $produk = Produk::where('sku', $firstItem->sku)->first();
                $tanggal = Carbon::parse($firstItem->pesanan->tanggal);

                return [
                    'sku' => $firstItem->sku,
                    'nama_produk' => $produk?->nama_produk ?? $firstItem->nama_produk,
                    'variasi' => $produk?->variasi ?? $firstItem->variasi,
                    'qty' => $items->sum('jumlah'),
                    'diproses' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'proses')
                        ->sum('jumlah'),
                    'kirim' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'kirim')
                        ->sum('jumlah'),
                    'retur' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'pengiriman gagal' && $item->pesanan->status === 'pengembalian')
                        ->sum('jumlah'),
                    'selesai' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'selesai')
                        ->sum('jumlah'),
                    'bulan' => $tanggal->month,
                    'tahun' => $tanggal->year,
                    'tanggal_awal' => $tanggal->copy()->startOfMonth(),
                    'tanggal_akhir' => $tanggal->copy()->endOfMonth(),
                    'data' => $items->values(),
                ];
            })
            ->values();

        return view('gudang.produk_custom', compact('data'));
    }

    // ==================================================//
    // ================== PRODUK ========================//
    // ==================================================//
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
                    'gudang_id' => auth()->id(),
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
                    'gudang_id' => auth()->id(),
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
                        'gudang_id' => auth()->id(),
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
                        'gudang_id' => auth()->id(),
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

        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan stok: '.$e->getMessage(),
            ], 500);
        }
    }

    public function stokExport()
    {
        return Excel::download(new gudangStokExport, 'Stok-'.now()->format('Y-m-d').'.xlsx');
    }

    public function stokImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $fullPath = null;
        try {
            $file = $request->file('file');
            $folder = storage_path('app/imports');
            if (! is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $namaFile = time().'_'.$file->getClientOriginalName();
            $fileBaru = $file->move($folder, $namaFile);
            $fullPath = $fileBaru->getPathname();
            $import = new stokImport;
            Excel::import($import, $fullPath);
            $perubahan = $import->getDataBerubah();
            $skuTidakDitemukan = $import->getSkuTidakDitemukan();

            if (empty($perubahan) && empty($skuTidakDitemukan)) {
                return response()->json([
                    'status' => false,
                    'type' => 'no_changes',
                    'message' => 'Tidak ada perubahan stok.',
                ]);
            }

            $token = (string) Str::uuid();
            Cache::put(
                'import_stok_'.$token,
                [
                    'perubahan' => $perubahan,
                    'sku_tidak_ditemukan' => $skuTidakDitemukan,
                ],
                now()->addMinutes(10)
            );

            return response()->json([
                'status' => true,
                'jumlah' => count($perubahan),
                'jumlah_tidak_ditemukan' => count($skuTidakDitemukan),
                'data' => $perubahan,
                'sku_tidak_ditemukan' => $skuTidakDitemukan,
                'token' => $token,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);

        } finally {
            if ($fullPath && file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function importUpdate(Request $request)
    {
        $request->validate([
            'dataBerubah.*.stok_produk_id' => ['required', 'integer', 'exists:stok_produks,id'],
            'dataBerubah.*.sku' => ['required', 'string', 'max:100'],
            'dataBerubah.*.stok_lama' => ['required', 'integer', 'min:0'],
            'dataBerubah.*.stok_baru' => ['required', 'integer', 'min:0'],
            'dataBerubah.*.selisih' => ['required', 'integer'],

            'skuBaru.*.sku' => ['required', 'string', 'max:100'],
            'skuBaru.*.stok_excel' => ['required', 'integer', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $dataBerubah = collect($request->input('dataBerubah', []))
                ->map(fn ($item) => (object) $item);

            $skuBaru = collect($request->input('skuBaru', []))
                ->map(fn ($item) => (object) $item);

            foreach ($dataBerubah as $value) {
                stok_produk::where('sku_id', $value->sku)->update([
                    'jumlah_tersedia' => $value->stok_baru,
                ]);

                $data = stok_produk::where('sku_id', $value->sku)->first();
                mutasi_stok::create([
                    'stok_produk_id' => $data->id,
                    'gudang_id' => Auth::user()->id,
                    'produksi_id' => null,
                    'adm_penjualan_id' => null,
                    'jenis_mutasi' => 'edit',
                    'jumlah' => $value->stok_baru,
                    'keterangan' => 'Penyesuai produk (stok awal '.$value->stok_lama.', stok akhir '.$value->stok_baru.')',
                ]);
            }

            foreach ($skuBaru as $value) {
                stok_produk::create([
                    'sku_id' => $value->sku,
                    'jumlah_tersedia' => $value->stok_excel,
                    'min_stok' => 5,
                ]);

                $data = stok_produk::where('sku_id', $value->sku)->first();
                mutasi_stok::create([
                    'stok_produk_id' => $data->id,
                    'gudang_id' => Auth::user()->id,
                    'produksi_id' => null,
                    'adm_penjualan_id' => null,
                    'jenis_mutasi' => 'masuk',
                    'jumlah' => $value->stok_excel,
                    'keterangan' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semua Data Berhasil di Simpan',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================================================//
    // ================== KATEGORI ======================//
    // ==================================================//
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

    // ==================================================//
    // ========== RIWAYAT AKTIVITAS =====================//
    // ==================================================//
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
            'stok_produk.produk.kategori', 'gudang', 'admin_penjualan',
        ])->whereIn('jenis_mutasi', ['keluar', 'masuk', 'edit']);

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
        $orderColumn = (int) $request->input('order.0.column', 5);
        $orderDirection = $request->input('order.0.dir', 'desc');
        $orderDirection = $orderDirection === 'asc' ? 'asc' : 'desc';
        if ($orderColumn === 5) {
            $query->orderBy('created_at', $orderDirection);
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
                'admin_gudang' => $item->gudang?->name ?? '-',
                'pengambil' => $item->admin_penjualan?->name ?? '-',
                'created_at' => $item->created_at,
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
}

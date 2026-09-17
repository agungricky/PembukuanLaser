<?php

namespace App\Services\Gudang;

use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\ResiPage;
use App\Models\stok_produk;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Yajra\DataTables\Facades\DataTables;

class TransaksiService
{
    public function detailpesanan($filter, $sku)
    {
        if ($filter === 'siapkan') {
            $data = PesananPerProduk::with('pesanan.toko', 'produk')
                ->where('sku', $sku)
                ->where('status_pesanan', '0')
                ->whereHas('pesanan', function ($query) {
                    $query->where('status', 'proses');
                })->get();

            return response()->json($data);
        } else {
            $kebutuhanProduk = PesananPerProduk::with('pesanan.toko', 'produk')->where('mutasi_stok_id', $sku)->get();

            return response()->json($kebutuhanProduk);
        }
    }

    private function siapkan()
    {
        $tanggalAwal = now()->subDays(6)->startOfDay();
        $tanggalAkhir = now()->endOfDay();

        // =========================================================
        // QUERY UTAMA YAJRA
        // =========================================================
        $data = Pesanan::query()
            ->with('pesanan_per_produk', 'toko')
            ->where('status', 'proses')
            ->whereBetween('tanggal', [
                $tanggalAwal,
                $tanggalAkhir,
            ])
            ->orderByRaw('batas_kirim_at IS NULL ASC')
            ->orderBy('batas_kirim_at', 'ASC');

        $marketplace = request('marketplace');
        if (! empty($marketplace)) {
            $data->whereHas('toko', function ($query) use ($marketplace) {
                $query->where(
                    'marketplace',
                    $marketplace
                );
            });
        }

        // =========================================================
        // AMBIL ANTRIAN PESANAN UNTUK PERHITUNGAN STOK
        // =========================================================
        $antrian = Pesanan::query()
            ->select([
                'no_pesanan',
                'batas_kirim_at',
            ])
            ->with([
                'pesanan_per_produk' => function ($query) {
                    $query->select([
                        'id_per_produk',
                        'no_pesanan',
                        'sku',
                        'jumlah',
                    ]);
                },
            ])
            ->where('status', 'proses')
            ->whereBetween('tanggal', [
                $tanggalAwal,
                $tanggalAkhir,
            ])
            ->orderByRaw('batas_kirim_at IS NULL ASC')
            ->orderBy('batas_kirim_at', 'ASC')
            ->get();

        // =========================================================
        // AMBIL SEMUA SKU
        // =========================================================
        $semuaSku = $antrian
            ->pluck('pesanan_per_produk')
            ->flatten()
            ->pluck('sku')
            ->filter()
            ->unique()
            ->values();

        // =========================================================
        // AMBIL STOK
        // =========================================================
        $stokProduk = stok_produk::query()
            ->whereIn('sku_id', $semuaSku)
            ->pluck(
                'jumlah_tersedia',
                'sku_id'
            );

        // =========================================================
        // HITUNG ALOKASI STOK
        // =========================================================
        $sisaStok = [];
        $alokasiStok = [];
        foreach ($antrian as $pesanan) {
            foreach ($pesanan->pesanan_per_produk as $item) {
                $sku = $item->sku;
                $jumlah = (int) $item->jumlah;

                // Pertama kali SKU ditemukan
                if (! array_key_exists($sku, $sisaStok)) {

                    $sisaStok[$sku] = (int) (
                        $stokProduk[$sku] ?? 0
                    );
                }

                $stokSebelum = $sisaStok[$sku];

                // Apakah stok cukup untuk pesanan ini?
                $tersedia = $stokSebelum >= $jumlah;

                // Kurangi stok berdasarkan urutan pesanan
                $sisaStok[$sku] = max(
                    0,
                    $stokSebelum - $jumlah
                );

                $alokasiStok[$item->id_per_produk] = [
                    'stok_awal' => $stokSebelum,
                    'jumlah' => $jumlah,
                    'stok_sisa' => $sisaStok[$sku],
                    'tersedia' => $tersedia,
                ];
            }
        }

        // =========================================================
        // TENTUKAN STATUS STOK PER PESANAN
        //
        // Semua item tersedia = Tersedia
        // Ada satu item kurang = Kurang
        // =========================================================
        $pesananTersedia = [];
        foreach ($antrian as $pesanan) {
            $semuaTersedia = true;
            foreach ($pesanan->pesanan_per_produk as $item) {
                $tersedia =
                    $alokasiStok[$item->id_per_produk]['tersedia']
                    ?? false;

                if (! $tersedia) {
                    $semuaTersedia = false;
                    break;
                }
            }

            if ($semuaTersedia) {
                $pesananTersedia[] =
                    $pesanan->no_pesanan;
            }
        }

        $pesananTersedia = array_values(
            array_unique($pesananTersedia)
        );

        // =========================================================
        // DATATABLE
        // =========================================================
        return DataTables::eloquent($data)
            // =====================================================
            // TAMBAHKAN STATUS STOK KE PESANAN_PER_PRODUK
            // =====================================================
            ->editColumn('pesanan_per_produk', function ($pesanan) use ($alokasiStok, $stokProduk) {

                return $pesanan->pesanan_per_produk
                    ->map(function ($item) use ($alokasiStok, $stokProduk) {

                        $alokasi = $alokasiStok[$item->id_per_produk] ?? null;

                        return [
                            'id_per_produk' => $item->id_per_produk,
                            'no_pesanan' => $item->no_pesanan,
                            'sku' => $item->sku,
                            'nama_produk' => $item->nama_produk,
                            'variasi' => $item->variasi,
                            'jumlah' => $item->jumlah,

                            // STOK ASLI DATABASE
                            'stok_total' => (int) ($stokProduk[$item->sku] ?? 0),

                            // ALOKASI ANTRIAN
                            'stok_awal' => $alokasi['stok_awal'] ?? 0,
                            'stok_sisa' => $alokasi['stok_sisa'] ?? 0,
                            'tersedia' => $alokasi['tersedia'] ?? false,
                        ];

                    })
                    ->values()
                    ->toArray();

            })

            // =====================================================
            // SEARCH SKU
            // =====================================================
            ->filterColumn(
                'pesanan_per_produk',
                function ($query, $keyword) {
                    $keyword = trim($keyword);
                    $query->whereHas(
                        'pesanan_per_produk',
                        function ($q) use ($keyword) {
                            $q->where(
                                'sku',
                                'like',
                                $keyword.'%'
                            );

                        }
                    );

                }
            )

            // =====================================================
            // SEARCH TOKO
            // =====================================================
            ->filterColumn(
                'no_resi',
                function ($query, $keyword) {
                    $keyword = trim($keyword);
                    $query->whereHas(
                        'toko',
                        function ($q) use ($keyword) {
                            $q->where(
                                'nama_toko',
                                'like',
                                '%'.$keyword.'%'
                            );

                        }
                    );
                }
            )

            // =====================================================
            // ORDER STATUS STOK
            //
            // ASC  = Tersedia dulu
            // DESC = Kurang dulu
            // =====================================================
            ->orderColumn(
                'status_stok',
                function ($query, $order) use ($pesananTersedia) {
                    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

                    // Hapus order bawaan sementara
                    // ketika kolom stok diklik
                    $query->reorder();
                    if (! empty($pesananTersedia)) {
                        $placeholders = implode(
                            ',',
                            array_fill(
                                0,
                                count($pesananTersedia),
                                '?'
                            )
                        );

                        $query->orderByRaw(
                            "
                        CASE
                            WHEN no_pesanan IN ($placeholders)
                                THEN 0
                            ELSE 1
                        END {$order}
                        ",
                            $pesananTersedia
                        );
                    }

                    // Kalau status sama,
                    // tetap urut berdasarkan batas kirim
                    $query
                        ->orderByRaw(
                            'batas_kirim_at IS NULL ASC'
                        )
                        ->orderBy(
                            'batas_kirim_at',
                            'ASC'
                        );

                }
            )

            ->toJson();
    }

    public function showdata($filter)
    {
        if ($filter === 'siapkan') {
            return $this->siapkan();
        } elseif ($filter === 'siap') {
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

    public function updateselesai(Request $request)
    {
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
            'message' => 'Data berhasil diupdate',
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
            'pesanan' => 'required|array|min:1',
            'pesanan.*' => 'required|string',
            'kebutuhan' => 'required|array|min:1',
            'kebutuhan.*.sku' => 'required|string',
            'kebutuhan.*.nama_produk' => 'required|string',
            'kebutuhan.*.stok_awal' => 'required|integer|min:0',
            'kebutuhan.*.kebutuhan' => 'required|integer|min:1',
            'alasan_export' => 'required|string',
        ]);

        $pesanan = ResiPage::with('resi_imports')->whereIn('no_pesanan', $request->pesanan)->get();
        dd($pesanan);


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
}

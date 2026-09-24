<?php

namespace App\Services\Gudang;

use App\Models\Exporter;
use App\Models\mutasi_stok;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\ResiPage;
use App\Models\stok_produk;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;
use Yajra\DataTables\Facades\DataTables;

class TransaksiService
{
    private $resi;

    private $pdf;

    private $stokProduk;

    private $produkPerPesanan;

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

        // QUERY UTAMA DATATABLE
        $data = Pesanan::query()
            ->with([
                'pesanan_per_produk.produk.kategori',
                'toko',
            ])
            ->where('status', 'proses')

            // Harus punya minimal 1 produk yang masih perlu diproses
            ->whereHas('pesanan_per_produk', function ($query) {
                $query
                    ->where('status_pesanan', '0')
                    ->whereNull('mutasi_stok_id');
            })

            // Kalau ada custom = 1, seluruh pesanan jangan ditampilkan
            ->whereDoesntHave('pesanan_per_produk', function ($query) {
                $query->where('custom', 1);
            })

            ->whereBetween('tanggal', [
                $tanggalAwal,
                $tanggalAkhir,
            ])

            ->orderByRaw('batas_kirim_at IS NULL ASC')
            ->orderBy('batas_kirim_at', 'ASC');

        // FILTER MARKETPLACE
        $marketplace = request('marketplace');
        if (! empty($marketplace)) {
            $data->whereHas('toko', function ($query) use ($marketplace) {
                $query->where('marketplace', $marketplace);
            });
        }

         $kategori = request('kategori');
        if (! empty($kategori)) {
            $data->whereHas(
                'pesanan_per_produk.produk',
                function ($query) use ($kategori) {
                    $query->where(
                        'kategori_id',
                        $kategori
                    );
                }
            );
        }

        // ANTRIAN STOK - QUERY RINGAN
        $antrian = DB::table('pesanan_per_produk as ppp')
            ->join(
                'pesanan as p',
                'p.no_pesanan',
                '=',
                'ppp.no_pesanan'
            )
            ->where('p.status', 'proses')
            ->where('ppp.status_pesanan', '0')
            ->where('ppp.custom', 0)
            ->whereNull('ppp.mutasi_stok_id')
            ->whereBetween('p.tanggal', [
                $tanggalAwal,
                $tanggalAkhir,
            ])
            ->select([
                'ppp.id_per_produk',
                'ppp.no_pesanan',
                'ppp.sku',
                'ppp.jumlah',
                'p.batas_kirim_at',
            ])
            ->orderByRaw(
                'p.batas_kirim_at IS NULL ASC'
            )
            ->orderBy(
                'p.batas_kirim_at',
                'ASC'
            )
            ->orderBy(
                'ppp.id_per_produk',
                'ASC'
            )
            ->get();

        // STOK
        $semuaSku = $antrian
            ->pluck('sku')
            ->filter()
            ->unique()
            ->values();

        $stokProduk = stok_produk::query()
            ->whereIn('sku_id', $semuaSku)
            ->pluck(
                'jumlah_tersedia',
                'sku_id'
            );

        // ALOKASI STOK
        $sisaStok = [];
        $alokasiStok = [];
        $statusPesanan = [];
        foreach ($antrian as $item) {
            $sku = $item->sku;
            $jumlah = (int) $item->jumlah;

            if (! isset($sisaStok[$sku])) {
                $sisaStok[$sku] = (int) (
                    $stokProduk[$sku] ?? 0
                );
            }

            $stokSebelum = $sisaStok[$sku];
            $tersedia = $stokSebelum >= $jumlah;
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

            if (! array_key_exists(
                $item->no_pesanan,
                $statusPesanan
            )) {
                $statusPesanan[$item->no_pesanan] = true;
            }

            if (! $tersedia) {
                $statusPesanan[$item->no_pesanan] = false;
            }
        }

        $pesananTersedia = array_keys(
            array_filter($statusPesanan)
        );

        // DATATABLE
        return DataTables::eloquent($data)
            ->editColumn(
                'pesanan_per_produk',
                function ($pesanan) use (
                    $alokasiStok,
                    $stokProduk
                ) {

                    return $pesanan
                        ->pesanan_per_produk
                        ->map(function ($item) use (
                            $alokasiStok,
                            $stokProduk
                        ) {
                            $alokasi =
                                $alokasiStok[
                                    $item->id_per_produk
                                ] ?? null;

                            return [
                                'id_per_produk' => $item->id_per_produk,
                                'no_pesanan' => $item->no_pesanan,
                                'sku' => $item->sku,
                                'nama_produk' => $item->nama_produk,
                                'variasi' => $item->variasi,
                                'jumlah' => $item->jumlah,
                                'stok_total' => (int) (
                                    $stokProduk[
                                        $item->sku
                                    ] ?? 0
                                ),
                                'stok_awal' => $alokasi['stok_awal'] ?? 0,
                                'stok_sisa' => $alokasi['stok_sisa'] ?? 0,
                                'tersedia' => $alokasi['tersedia'] ?? false,
                            ];
                        })
                        ->values()
                        ->toArray();
                }
            )

            ->filterColumn('input_at', function ($query, $keyword) {
                $keyword = trim($keyword);

                $query->where(function ($q) use ($keyword) {
                    $q->where(
                        'pesanan.no_pesanan',
                        'like',
                        '%'.$keyword.'%'
                    )
                        ->orWhere(
                            'pesanan.input_at',
                            'like',
                            '%'.$keyword.'%'
                        );
                });
            })

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

            ->filterColumn(
                'no_resi',
                function ($query, $keyword) {
                    $keyword = trim($keyword);
                    $query->where(function ($q) use ($keyword) {
                        $q->where(
                            'no_resi',
                            'like',
                            $keyword.'%'
                        )
                            ->orWhereHas(
                                'toko',
                                function ($toko) use ($keyword) {
                                    $toko->where(
                                        'nama_toko',
                                        'like',
                                        '%'.$keyword.'%'
                                    );
                                }
                            );
                    });
                }
            )

            ->orderColumn(
                'status_stok',
                function (
                    $query,
                    $order
                ) use ($pesananTersedia) {
                    $order =
                        strtolower($order) === 'desc'
                        ? 'DESC'
                        : 'ASC';

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

            ->orderColumn('input_at', function ($query, $order) {
                $query->reorder()
                    ->orderBy('pesanan.input_at', $order);
            })

            ->toJson();
    }

    private function diambil()
    {
        $tanggalAwal = now()->subDays(30)->startOfDay();
        $tanggalAkhir = now()->endOfDay();

        $data = Pesanan::query()
            ->with([
                'pesanan_per_produk.mutasi.gudang',
                'pesanan_per_produk.mutasi.admin_penjualan',
                'toko',
            ])

            // Harus punya produk status 1 dan sudah mutasi
            ->whereIn('no_pesanan', function ($query) {
                $query->select('no_pesanan')
                    ->from('pesanan_per_produk')
                    ->where('status_pesanan', '1')
                    ->whereNotNull('mutasi_stok_id');
            })

            // Kalau ada satu saja status 0, buang pesanan tersebut
            ->whereNotIn('no_pesanan', function ($query) {
                $query->select('no_pesanan')
                    ->from('pesanan_per_produk')
                    ->where('status_pesanan', '0');
            })
            ->whereBetween('tanggal', [
                $tanggalAwal,
                $tanggalAkhir,
            ])

            // Urut berdasarkan updated_at terbaru dari pesanan_per_produk
            ->orderByDesc(
                PesananPerProduk::selectRaw('MAX(updated_at)')
                    ->whereColumn(
                        'pesanan_per_produk.no_pesanan',
                        'pesanan.no_pesanan'
                    )
            );

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
        // DATATABLE
        // =========================================================
        return DataTables::eloquent($data)
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
            ->toJson();
    }

    public function showdata($filter)
    {
        if ($filter === 'siapkan') {
            return $this->siapkan();
        } elseif ($filter === 'diambil') {
            return $this->diambil();
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
            PesananPerProduk::whereIn('no_pesanan', $request->no_pesanan)
                ->update(['status_pesanan' => '1']);

            $kebutuhan = collect($request->input('kebutuhan'));
            $sku = $kebutuhan->pluck('sku');
            $skuAda = stok_produk::whereIn('sku_id', $sku)->pluck('sku_id');
            $skuTidakAda = $sku->diff($skuAda);

            if ($skuTidakAda->isNotEmpty()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Ada SKU yang belum tercatat di stok.',
                    'sku_tidak_ada' => $skuTidakAda->values(),
                ], 422);
            }

            foreach ($kebutuhan as $value) {
                $data_stok = stok_produk::where('sku_id', $value['sku'])->first();

                $mutasi = mutasi_stok::create([
                    'stok_produk_id' => $data_stok->id,
                    'adm_penjualan_id' => $request->pengambil_barang,
                    'gudang_id' => Auth::id(),
                    'jenis_mutasi' => 'keluar',
                    'jumlah' => $value['kebutuhan'],
                    'produksi_id' => null,
                    'keterangan' => null,
                ]);

                stok_produk::where('sku_id', $value['sku'])
                    ->decrement(
                        'jumlah_tersedia',
                        $value['kebutuhan']
                    );

                PesananPerProduk::whereIn(
                    'no_pesanan',
                    $request->no_pesanan
                )
                    ->where('sku', $value['sku'])
                    ->update([
                        'mutasi_stok_id' => $mutasi->id,
                    ]);
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

    // private function belumdiImport($noPesanan)
    // {
    //     $resiPages = ResiPage::with('resi_imports')->whereIn('no_pesanan', $noPesanan)->get();
    //     $pesananDitemukan = $resiPages->pluck('no_pesanan')->unique();
    //     $pesananTidakDitemukan = collect($noPesanan)->diff($pesananDitemukan);
    //     $tidakDitemukan = Pesanan::join(
    //         'pesanan_per_produk',
    //         'pesanan.no_pesanan',
    //         '=',
    //         'pesanan_per_produk.no_pesanan'
    //     )
    //         ->whereIn('pesanan.no_pesanan', $pesananTidakDitemukan)
    //         ->get([
    //             'pesanan.no_pesanan',
    //             'pesanan.no_resi',
    //             'pesanan_per_produk.sku',
    //         ]);

    //     if ($pesananTidakDitemukan->isNotEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terdapat Pesanan yang Resinya Belum di Import.',
    //             'tidak_ditemukan' => $tidakDitemukan->values(),
    //         ], 422);
    //     }
    // }

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

    private function viewResi($request)
    {
        $daftarPesanan = collect($request->pesanan);
        $pesananSudahImport = ResiPage::with('resi_imports')
            ->whereIn('no_pesanan', $daftarPesanan)
            ->get();

        $noPesananSudahImport = $pesananSudahImport
            ->pluck('no_pesanan');

        $noPesananBelumImport = $daftarPesanan
            ->diff($noPesananSudahImport)
            ->values();

        $cekImport = [
            'sudah_import' => $noPesananSudahImport->values(),
            'belum_import' => $noPesananBelumImport,
        ];

        if ($cekImport['belum_import']->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat Pesanan Belum di Import, Mohon Import Resi Terlebih Dahulu',
                'data' => $cekImport['belum_import'],
            ], 422);
        }

        $resi = ResiPage::with('resi_imports')
            ->whereIn(
                'no_pesanan',
                $cekImport['sudah_import']
            )
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

        $token = (string) Str::uuid();
        $tempFolder = 'temp/resi/'.$token;
        Storage::disk('local')->makeDirectory(
            $tempFolder
        );

        $pdf = new Fpdi;
        $pdf->SetMargins(0, 2, 0);
        $pdf->SetAutoPageBreak(false, 0);

        $drawTableHeader = function () use ($pdf) {
            $pdf->SetFont('Courier', 'B', 8);
            $pdf->Cell(8, 5, 'NO', 0, 0, 'L');
            $pdf->Cell(20, 5, 'SKU', 0, 0, 'L');
            $pdf->Cell(50, 5, 'NAMA PRODUK', 0, 0, 'L');
            $pdf->Cell(15, 5, 'SISA STOK', 0, 0, 'C');
            $pdf->Cell(10, 5, 'BUTUH', 0, 1, 'C');

            // Garis bawah header
            $pdf->SetFont('Courier', '', 6);
            $pdf->Cell(0, 3, str_repeat('-', 110), 0, 1, 'L');

            // Kembalikan font
            $pdf->SetFont('Courier', '', 8);
        };

        // HELPER HALAMAN LANJUTAN
        $addContinuationPage = function () use ($pdf, $drawTableHeader) {
            $pdf->AddPage('P', [150, 105]);
            $pdf->SetFont('Courier', 'B', 10);
            $pdf->Cell(0, 6, 'LAPORAN CETAK RESI - LANJUTAN', 0, 1, 'C');
            $pdf->SetFont('Courier', '', 7);
            $pdf->Cell(0, 3, str_repeat('=', 88), 0, 1, 'L');
            $drawTableHeader();
        };

        // HALAMAN PERTAMA LAPORAN
        $pdf->AddPage('P', [150, 105]);
        $pdf->SetFont('Courier', 'B', 12);
        $pdf->Cell(0, 7, 'LAPORAN CETAK RESI', 0, 1, 'C');

        // Garis Pemisah ( ========= )
        $pdf->SetFont('Courier', '', 8);
        $pdf->Cell(0, 4, str_repeat('=', 88), 0, 1, 'L');

        // INFORMASI CETAK
        $pdf->SetFont('Courier', '', 8);

        // TANGGAL
        $pdf->Cell(25, 5, 'TANGGAL', 0, 0);
        $pdf->Cell(0, 5, ': '.now()->format('d-m-Y H:i'), 0, 1);

        // CETAK BY
        $pdf->Cell(25, 5, 'CETAK BY', 0, 0);
        $pdf->Cell(0, 5, ': '.strtoupper(Auth::user()->name ?? '-'), 0, 1);

        // KETERANGAN
        $pdf->Cell(25, 5, 'KETERANGAN', 0, 0);
        $pdf->Cell(0, 5, ': '.strtoupper($request->alasan_export ?? '-'), 0, 1);

        // GARIS (---------------------)
        $pdf->Cell(0, 4, str_repeat('-', 88), 0, 1, 'L');

        // HEADER TABEL
        $drawTableHeader();

        // Isi Tabel Laporan
        $pdf->SetFont('Courier', '', 8);
        $totalKebutuhan = 0;
        foreach ($request->kebutuhan as $index => $item) {
            // Nama Produk
            $kata = preg_split('/\s+/', trim(strtoupper($item['nama_produk'])));
            $barisNama = array_chunk($kata, 4);
            $namaProduk = implode("\n", array_map(fn ($row) => implode(' ', $row), $barisNama));

            // Variasi
            $variasi = trim(strtoupper($item['variasi'] ?? ''));

            // HITUNG TINGGI ROW
            $tinggiNama = 4;
            $tinggiVariasi = 4;
            $jumlahBarisNama = max(count($barisNama), 1);
            $tinggiRow = ($jumlahBarisNama * $tinggiNama) + ($variasi !== '' ? $tinggiVariasi : 0);
            $tinggiRow += 1;

            // CEK APAKAH ROW MASIH MUAT
            $tinggiGaris = 3;
            $batasBawah = $pdf->GetPageHeight() - 4;
            $posisiAkhirRow = $pdf->GetY() + $tinggiRow + $tinggiGaris;

            if ($posisiAkhirRow > $batasBawah) {
                $addContinuationPage();
            }

            // POSISI AWAL ROW
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // UKURAN KOLOM
            $lebarNo = 8;
            $lebarSku = 20;
            $lebarNama = 50;
            $lebarStok = 15;
            $lebarButuh = 10;

            // NO
            $pdf->SetXY($x, $y);
            $pdf->SetFont('Courier', '', 8);
            $pdf->Cell($lebarNo, $tinggiRow, $index + 1, 0, 0, 'C');

            // SKU
            $pdf->SetXY($x + $lebarNo, $y);
            $pdf->SetFont('Courier', 'B', 8);
            $pdf->Cell($lebarSku, $tinggiRow, $item['sku'], 0, 0, 'L');

            // NAMA PRODUK
            $xNama = $x + $lebarNo + $lebarSku;
            $pdf->SetXY($xNama, $y);
            $pdf->SetFont('Courier', 'B', 8);
            $pdf->MultiCell($lebarNama, $tinggiNama, $namaProduk, 0, 'L');

            // VARIASI
            if ($variasi !== '') {
                $yVariasi = $y + ($jumlahBarisNama * $tinggiNama);
                $pdf->SetXY($xNama, $yVariasi);
                $pdf->SetFont('Courier', '', 7);
                $pdf->Cell($lebarNama, $tinggiVariasi, '- '.$variasi, 0, 0, 'L');
            }

            // SISA STOK
            $xStok = $x + $lebarNo + $lebarSku + $lebarNama;
            $pdf->SetXY($xStok, $y);
            $pdf->SetFont('Courier', '', 8);
            $pdf->Cell($lebarStok, $tinggiRow, max(0, $item['stok_awal'] - $item['kebutuhan']), 0, 0, 'C');

            // KEBUTUHAN
            $xButuh = $xStok + $lebarStok;
            $pdf->SetXY($xButuh, $y);
            $pdf->Cell($lebarButuh, $tinggiRow, $item['kebutuhan'], 0, 0, 'C');

            // GARIS PEMISAH ROW ( ----------- )
            $yBawah = $y + $tinggiRow;
            $pdf->SetXY($x, $yBawah);
            $pdf->SetFont('Courier', '', 6);
            $pdf->Cell(0, 3, str_repeat('-', 110), 0, 1, 'L');

            // PINDAH KE ROW BERIKUTNYA
            $pdf->SetFont('Courier', '', 8);
            $pdf->SetXY($x, $yBawah + 3);

            // TOTAL
            $totalKebutuhan += (int) $item['kebutuhan'];
        }

        // CEK RUANG UNTUK TOTAL + INFO
        $tinggiFooter = 20;
        if (($pdf->GetY() + $tinggiFooter) > ($pdf->GetPageHeight() - 4)) {
            $pdf->AddPage('P', [150, 105]);
            $pdf->SetFont('Courier', 'B', 10);
            $pdf->Cell(0, 6, 'LAPORAN CETAK RESI - RINGKASAN', 0, 1, 'C');
            $pdf->SetFont('Courier', '', 7);
            $pdf->Cell(0, 3, str_repeat('=', 88), 0, 1, 'L');
        }

        // TOTAL LAPORAN
        $pdf->SetFont('Courier', 'B', 7);
        $pdf->SetX(0);

        $marginKiri = 0;
        $marginKanan = 3;
        $lebarArea = $pdf->GetPageWidth() - $marginKiri - $marginKanan;
        $lebarTotal = $lebarArea / 2;

        // TOTAL SKU
        $pdf->Cell($lebarTotal, 6, 'TOTAL SKU : '.count($request->kebutuhan), 0, 0, 'C');

        // TOTAL KEBUTUHAN
        $pdf->Cell($lebarTotal, 6, 'TOTAL KEBUTUHAN : '.$totalKebutuhan, 0, 1, 'C');

        // INFO
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $ukuranIcon = 3;
        $xIcon = $x + 2;
        $yIcon = $y;

        $pdf->SetLineWidth(0.2);

        // Segitiga
        $pdf->Line($xIcon, $yIcon + $ukuranIcon, $xIcon + ($ukuranIcon / 2), $yIcon);
        $pdf->Line($xIcon + ($ukuranIcon / 2), $yIcon, $xIcon + $ukuranIcon, $yIcon + $ukuranIcon);
        $pdf->Line($xIcon + $ukuranIcon, $yIcon + $ukuranIcon, $xIcon, $yIcon + $ukuranIcon);

        // Tanda seru
        $pdf->SetFont('Courier', 'B', 5);

        $tandaSeru = '!';
        $lebarTandaSeru = $pdf->GetStringWidth($tandaSeru);
        $xTandaSeru = $xIcon + (($ukuranIcon - $lebarTandaSeru) / 2);
        $yTandaSeru = $yIcon + 2.2;
        $pdf->Text($xTandaSeru, $yTandaSeru, $tandaSeru);

        // Text info
        $pdf->SetFont('Courier', '', 7);
        $pdf->SetXY($x + $ukuranIcon + 2, $y);
        $pdf->MultiCell($lebarArea - $ukuranIcon - 2, 4, 'INFO: Stok adalah sisa akhir setelah barang diambil dari gudang.', 0, 'L');

        // GARIS PENUTUP
        $pdf->SetX(0);
        $pdf->SetFont('Courier', '', 7);
        $pdf->Cell(0, 3, str_repeat('=', 90), 0, 1, 'L');

        // HALAMAN RESI
        $produkPerPesanan = PesananPerProduk::query()
            ->whereIn('no_pesanan', $cekImport['sudah_import'])
            ->get()
            ->groupBy('no_pesanan');

        // AMBIL SEMUA SKU
        $semuaSku = $produkPerPesanan
            ->flatten()
            ->pluck('sku')
            ->filter()
            ->unique()
            ->values();

        // AMBIL STOK
        $stokProduk = stok_produk::query()
            ->whereIn('sku_id', $semuaSku)
            ->pluck('jumlah_tersedia', 'sku_id');

        $this->resi = $resi;
        $this->pdf = $pdf;
        $this->stokProduk = $stokProduk;
        $this->produkPerPesanan = $produkPerPesanan;

        // Proses Pencarian Resi
        $this->prosesResi();

        // SIMPAN PDF
        $relativePath = $tempFolder.'/preview.pdf';
        $outputPath = Storage::disk('local')->path($relativePath);
        $pdf->Output('F', $outputPath);

        return response()->json([
            'success' => true,
            'message' => 'Resi berhasil diproses.',
            'preview_url' => route('transaksi.preview-resi', ['token' => $token]),
        ]);
    }

    private function prosesResi()
    {
        $resi = $this->resi;
        $pdf = $this->pdf;
        $stokProduk = $this->stokProduk;
        $produkPerPesanan = $this->produkPerPesanan;

        foreach ($resi as $item) {
            $sourcePath = Storage::disk('local')->path($item['path_file']);
            $pageCount = $pdf->setSourceFile($sourcePath);

            if ($item['halaman'] < 1 || $item['halaman'] > $pageCount) {
                throw new Exception(
                    'Halaman '.$item['halaman']
                    .' tidak ditemukan pada file '
                    .$item['nama_file']
                );
            }

            $templateId = $pdf->importPage($item['halaman']);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage(
                $size['orientation'],
                [$size['width'], $size['height']]
            );

            $pdf->useTemplate($templateId);

            $produkPesanan = $produkPerPesanan[$item['no_pesanan']] ?? collect();
            $produkPesanan = $produkPesanan->values();

            if ($produkPesanan->count() <= 1) {
                continue;
            }

            $statusProduk = $produkPesanan->map(function ($produk) use ($stokProduk) {
                $sku = strtoupper(trim($produk->sku ?? ''));
                $jumlah = (int) ($produk->jumlah ?? 0);
                $stok = (int) ($stokProduk[$sku] ?? 0);

                $tersedia = $stok > 0 && $stok >= $jumlah;

                return [
                    'produk' => $produk,
                    'sku' => $sku,
                    'jumlah' => $jumlah,
                    'stok' => $stok,
                    'tersedia' => $tersedia,
                ];
            });

            $statusProduk = $this->urutkanProdukSesuaiPdf(
                $statusProduk,
                $sourcePath,
                (int) $item['halaman']
            );

            $adaStokKurang = $statusProduk->contains(function ($produk) {
                return ! $produk['tersedia'];
            });

            if (! $adaStokKurang) {
                continue;
            }

            $namaFile = strtolower($item['nama_file'] ?? '');

            if (str_contains($namaFile, 'tiktok')) {
                $qtyX = 86;
                $qtyY = 72;
                $jarakDasar = 13;
            } else {
                $qtyX = 90;
                $qtyY = 93;
                $jarakDasar = 12;
            }

            foreach ($statusProduk as $status) {

                $this->gambarStatusStok(
                    $pdf,
                    $qtyX,
                    $qtyY,
                    $status['tersedia']
                );

                $produk = $status['produk'];

                $namaProduk = trim(
                    $produk->nama_produk
                    ?? $produk->nama
                    ?? $produk->product_name
                    ?? ''
                );

                $panjang = mb_strlen($namaProduk);

                $tambahanJarak = 0;

                if ($panjang > 30) {
                    $tambahanJarak = 0;
                }

                if ($panjang > 40) {
                    $tambahanJarak = 0;
                }

                if ($panjang > 50) {
                    $tambahanJarak = 0;
                }

                $qtyY += $jarakDasar + $tambahanJarak;
            }

            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetLineWidth(0.2);
        }
    }

    private function gambarStatusStok($pdf, float $x, float $y, bool $tersedia)
    {
        $ukuran = 4;

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.4);

        $pdf->Rect($x, $y, $ukuran, $ukuran);

        if ($tersedia) {
            // Centang
            $pdf->Line(
                $x + 0.8,
                $y + 2.1,
                $x + 1.7,
                $y + 3
            );

            $pdf->Line(
                $x + 1.7,
                $y + 3,
                $x + 3.4,
                $y + 0.9
            );
        } else {
            // Silang
            $pdf->Line(
                $x + 0.8,
                $y + 0.8,
                $x + 3.2,
                $y + 3.2
            );

            $pdf->Line(
                $x + 3.2,
                $y + 0.8,
                $x + 0.8,
                $y + 3.2
            );
        }
    }

    private function urutkanProdukSesuaiPdf($statusProduk, string $sourcePath, int $halaman)
    {
        static $pdfCache = [];

        if (! isset($pdfCache[$sourcePath])) {
            $parser = new Parser;
            $pdfCache[$sourcePath] = $parser->parseFile($sourcePath);
        }

        $pages = $pdfCache[$sourcePath]->getPages();
        $pageIndex = $halaman - 1;

        if (! isset($pages[$pageIndex])) {
            return $statusProduk->values();
        }

        $textPdf = strtoupper(
            $pages[$pageIndex]->getText()
        );

        $textPdf = preg_replace('/[^A-Z0-9]/', '', $textPdf);

        return $statusProduk
            ->map(function ($produk) use ($textPdf) {
                $sku = preg_replace('/[^A-Z0-9]/', '', strtoupper($produk['sku']));
                $posisi = strpos($textPdf, $sku);
                $produk['posisi_pdf'] = $posisi === false ? PHP_INT_MAX : $posisi;

                return $produk;
            })
            ->sortBy('posisi_pdf')
            ->values()
            ->map(function ($produk) {
                unset($produk['posisi_pdf']);

                return $produk;
            });
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

        DB::beginTransaction();
        try {
            $response = $this->viewResi($request);
            $exporter = Exporter::create([
                'user_id' => Auth::id(),
                'role' => 'gudang',
                'source_type' => 'stok',
                'status' => 'proses',
                'keterangan' => $request->alasan_export,
            ]);

            PesananPerProduk::whereIn('no_pesanan', $request->pesanan)->update([
                'exporter_id' => $exporter->id,
            ]);

            DB::commit();

            return $response;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }
}

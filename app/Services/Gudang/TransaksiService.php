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

            // Harus punya produk status 0 dan belum mutasi
            ->whereIn('no_pesanan', function ($query) {
                $query->select('no_pesanan')
                    ->from('pesanan_per_produk')
                    ->where('status_pesanan', '0')
                    ->whereNull('mutasi_stok_id');
            })

            // Kalau ada satu saja status 1, buang pesanan tersebut
            ->whereNotIn('no_pesanan', function ($query) {
                $query->select('no_pesanan')
                    ->from('pesanan_per_produk')
                    ->where('status_pesanan', '1');
            })
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

        $resi = ResiPage::whereIn('no_pesanan', $cekImport['sudah_import'])
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
        $tempFolder = 'temp/resi/'.$token;
        Storage::disk('local')->makeDirectory(
            $tempFolder
        );

        $pdf = new Fpdi;

        // ============================================
        // PENGATURAN HALAMAN LAPORAN
        // ============================================

        // Margin kiri 0, atas 2 mm, kanan 0
        $pdf->SetMargins(0, 2, 0);
        $pdf->SetAutoPageBreak(false, 0);

        $pdf->AddPage('P', [150, 105]);

        // ============================================
        // JUDUL
        // ============================================

        // Courier = gaya seperti mesin ketik / monospace
        $pdf->SetFont('Courier', 'B', 12);

        $pdf->Cell(
            0,
            7,
            'LAPORAN CETAK RESI',
            0,
            1,
            'C'
        );

        // Garis pemisah
        $pdf->SetFont('Courier', '', 8);

        $pdf->Cell(
            0,
            4,
            str_repeat('=', 88),
            0,
            1,
            'L'
        );

        // ============================================
        // INFORMASI CETAK
        // ============================================
        $pdf->SetFont('Courier', '', 8);

        $pdf->Cell(25, 5, 'TANGGAL', 0, 0);
        $pdf->Cell(
            0,
            5,
            ': '.now()->format('d-m-Y H:i'),
            0,
            1
        );

        $pdf->Cell(25, 5, 'CETAK BY', 0, 0);
        $pdf->Cell(
            0,
            5,
            ': '.strtoupper(Auth::user()->name ?? '-'),
            0,
            1
        );

        $pdf->Cell(25, 5, 'KETERANGAN', 0, 0);
        $pdf->Cell(
            0,
            5,
            ': '.strtoupper($request->alasan_export ?? '-'),
            0,
            1
        );

        // ============================================
        // GARIS PENUTUP INFORMASI
        // ============================================

        $pdf->Cell(
            0,
            4,
            str_repeat('-', 88),
            0,
            1,
            'L'
        );

        // ============================================
        // HEADER TABEL
        // ============================================

        $pdf->SetFont('Courier', 'B', 8);
        // Header
        $pdf->Cell(8, 5, 'NO', 0, 0, 'L');
        $pdf->Cell(18, 5, 'SKU', 0, 0, 'L');
        $pdf->Cell(50, 5, 'NAMA PRODUK', 0, 0, 'L');
        $pdf->Cell(15, 5, 'SISA STOK', 0, 0, 'C');
        $pdf->Cell(0, 5, 'BUTUH', 0, 1, 'C');

        // Garis bawah header
        $pdf->Cell(
            0,
            4,
            str_repeat('-', 88),
            0,
            1,
            'L'
        );

        // ============================================
        // ISI TABEL
        // ============================================

        $pdf->SetFont('Courier', '', 8);

        $totalKebutuhan = 0;
        foreach ($request->kebutuhan as $index => $item) {

            // ============================================
            // PECAH NAMA PRODUK SETIAP 4 KATA
            // ============================================

            $kata = preg_split(
                '/\s+/',
                trim(strtoupper($item['nama_produk']))
            );

            $barisNama = array_chunk(
                $kata,
                4
            );

            $namaProduk = implode(
                "\n",
                array_map(
                    fn ($row) => implode(' ', $row),
                    $barisNama
                )
            );

            // ============================================
            // VARIASI
            // ============================================

            $variasi = trim(
                strtoupper(
                    $item['variasi'] ?? ''
                )
            );

            // ============================================
            // TINGGI
            // ============================================

            $tinggiNama = 4;
            $tinggiVariasi = 4;

            $jumlahBarisNama = max(
                count($barisNama),
                1
            );

            $tinggiRow =
                ($jumlahBarisNama * $tinggiNama)
                + ($variasi !== '' ? $tinggiVariasi : 0);

            // Sedikit ruang bawah
            $tinggiRow += 1;

            // ============================================
            // POSISI AWAL ROW
            // ============================================

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // ============================================
            // UKURAN KOLOM
            // ============================================

            $lebarNo = 8;
            $lebarSku = 20;
            $lebarNama = 50;
            $lebarStok = 15;
            $lebarButuh = 10;

            // ============================================
            // NO
            // ============================================

            $pdf->SetXY(
                $x,
                $y
            );

            $pdf->SetFont(
                'Courier',
                '',
                8
            );

            $pdf->Cell(
                $lebarNo,
                $tinggiRow,
                $index + 1,
                0,
                0,
                'C'
            );

            // ============================================
            // SKU
            // ============================================

            $pdf->SetXY(
                $x + $lebarNo,
                $y
            );

            $pdf->SetFont(
                'Courier',
                'B',
                8
            );

            $pdf->Cell(
                $lebarSku,
                $tinggiRow,
                $item['sku'],
                0,
                0,
                'L'
            );

            // ============================================
            // NAMA PRODUK
            // ============================================

            $xNama =
                $x
                + $lebarNo
                + $lebarSku;

            $pdf->SetXY(
                $xNama,
                $y
            );

            // Nama produk dibuat bold
            $pdf->SetFont(
                'Courier',
                'B',
                8
            );

            $pdf->MultiCell(
                $lebarNama,
                $tinggiNama,
                $namaProduk,
                0,
                'L'
            );

            // ============================================
            // VARIASI
            // ============================================

            if ($variasi !== '') {

                $yVariasi =
                    $y
                    + ($jumlahBarisNama * $tinggiNama);

                $pdf->SetXY(
                    $xNama,
                    $yVariasi
                );

                // Variasi dibuat lebih kecil
                $pdf->SetFont(
                    'Courier',
                    '',
                    7
                );

                $pdf->Cell(
                    $lebarNama,
                    $tinggiVariasi,
                    '- '.$variasi,
                    0,
                    0,
                    'L'
                );
            }

            // ============================================
            // STOK
            // ============================================

            $pdf->SetXY(
                $x
                + $lebarNo
                + $lebarSku
                + $lebarNama,
                $y
            );

            $pdf->SetFont(
                'Courier',
                '',
                8
            );

            $pdf->Cell(
                $lebarStok,
                $tinggiRow,
                $item['stok_awal'] - $item['kebutuhan'],
                0,
                0,
                'C'
            );

            // ============================================
            // KEBUTUHAN
            // ============================================

            $pdf->SetXY(
                $x
                + $lebarNo
                + $lebarSku
                + $lebarNama
                + $lebarStok,
                $y
            );

            $pdf->Cell(
                $lebarButuh,
                $tinggiRow,
                $item['kebutuhan'],
                0,
                0,
                'C'
            );

            // ============================================
            // GARIS PEMISAH ROW
            // ============================================

            $yBawah =
                $y
                + $tinggiRow;

            $pdf->SetXY(
                $x,
                $yBawah
            );

            // Garis seperti mesin ketik
            $pdf->SetFont(
                'Courier',
                '',
                6
            );

            $pdf->Cell(
                0,
                3,
                str_repeat('-', 110),
                0,
                1,
                'L'
            );

            // ============================================
            // KEMBALIKAN FONT
            // ============================================

            $pdf->SetFont(
                'Courier',
                '',
                8
            );

            // ============================================
            // PINDAH KE ROW BERIKUTNYA
            // ============================================

            $pdf->SetXY(
                $x,
                $yBawah + 3
            );

            // ============================================
            // TOTAL
            // ============================================

            $totalKebutuhan +=
                (int) $item['kebutuhan'];
        }

        // ============================================
        // TOTAL LAPORAN
        // ============================================
        $pdf->SetFont(
            'Courier',
            'B',
            7
        );

        // Lebar area yang tersedia
        $lebarHalaman = $pdf->GetPageWidth();
        $marginKiri = $pdf->GetX();
        $marginKanan = 3;

        $lebarArea =
            $lebarHalaman
            - $marginKiri
            - $marginKanan;

        // Dibagi 2
        $lebarTotal = $lebarArea / 2;

        // ============================================
        // TOTAL SKU
        // ============================================

        $pdf->Cell(
            $lebarTotal,
            6,
            'TOTAL SKU : '.count($request->kebutuhan),
            0,
            0,
            'C'
        );

        // ============================================
        // TOTAL KEBUTUHAN
        // ============================================

        $pdf->Cell(
            $lebarTotal,
            6,
            'TOTAL KEBUTUHAN : '.$totalKebutuhan,
            0,
            1,
            'C'
        );

        // ============================================
        // INFO
        // ============================================

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // ============================================
        // ICON PERINGATAN
        // ============================================

        $ukuranIcon = 3;

        // Geser icon ke kanan
        $xIcon = $x + 2;
        $yIcon = $y;

        $pdf->SetLineWidth(0.2);

        // ============================================
        // SEGITIGA
        // ============================================
        $pdf->Line(
            $xIcon,
            $yIcon + $ukuranIcon,
            $xIcon + ($ukuranIcon / 2),
            $yIcon
        );

        // atas tengah → kanan bawah
        $pdf->Line(
            $xIcon + ($ukuranIcon / 2),
            $yIcon,
            $xIcon + $ukuranIcon,
            $yIcon + $ukuranIcon
        );

        // kanan bawah → kiri bawah
        $pdf->Line(
            $xIcon + $ukuranIcon,
            $yIcon + $ukuranIcon,
            $xIcon,
            $yIcon + $ukuranIcon
        );

        // ============================================
        // TANDA SERU
        // ============================================

        $pdf->SetFont(
            'Courier',
            'B',
            5
        );

        $tandaSeru = '!';

        // Hitung lebar tanda seru
        $lebarTandaSeru = $pdf->GetStringWidth(
            $tandaSeru
        );

        // Posisi horizontal tepat di tengah segitiga
        $xTandaSeru =
            $xIcon
            + (($ukuranIcon - $lebarTandaSeru) / 2);

        // Posisi vertikal
        $yTandaSeru =
            $yIcon + 2.2;

        // Tulis langsung supaya posisi lebih presisi
        $pdf->Text(
            $xTandaSeru,
            $yTandaSeru,
            $tandaSeru
        );

        // ============================================
        // TEXT INFO
        // ============================================

        $pdf->SetFont(
            'Courier',
            '',
            7
        );

        $pdf->SetXY(
            $x + $ukuranIcon + 2,
            $y
        );

        $pdf->MultiCell(
            $lebarArea - $ukuranIcon - 2,
            4,
            'INFO: Stok adalah sisa akhir setelah barang diambil dari gudang.',
            0,
            'L'
        );
        // ============================================
        // GARIS PENUTUP
        // ============================================

        $pdf->SetFont(
            'Courier',
            '',
            7
        );

        $pdf->Cell(
            0,
            3,
            str_repeat('=', 90),
            0,
            1,
            'L'
        );

        // ============================================
        // HALAMAN RESI
        // ============================================

        foreach ($resi as $item) {

            $sourcePath = Storage::disk('local')->path(
                $item['path_file']
            );

            $pageCount = $pdf->setSourceFile(
                $sourcePath
            );

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

            $templateId = $pdf->importPage(
                $item['halaman']
            );

            $size = $pdf->getTemplateSize(
                $templateId
            );

            $pdf->AddPage(
                $size['orientation'],
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate(
                $templateId
            );
        }

        // ============================================
        // SIMPAN
        // ============================================

        $relativePath =
            $tempFolder.'/preview.pdf';

        $outputPath =
            Storage::disk('local')->path(
                $relativePath
            );

        $pdf->Output(
            'F',
            $outputPath
        );

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

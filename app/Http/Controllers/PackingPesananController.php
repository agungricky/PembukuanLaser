<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class PackingPesananController extends Controller
{
    private const PACKING_MARKETPLACES = [
        'Shopee',
        'Tiktok',
    ];

    public function index(Request $request)
    {
        $query = Pesanan::with([
            'toko:id_toko,nama_toko,marketplace',
        ])
            ->where('status', 'proses')
            ->orderByDesc('tanggal')
            ->orderByDesc('no_pesanan');

        if ($request->filled('no_pesanan')) {
            $keyword = trim(
                (string) $request->no_pesanan
            );

            $query->where(function ($q) use ($keyword) {
                $q->where(
                    'no_pesanan',
                    'like',
                    "%{$keyword}%"
                )
                    ->orWhere(
                        'no_resi',
                        'like',
                        "%{$keyword}%"
                    );
            });
        }

        if ($request->filled('tanggal')) {
            $raw = trim(
                (string) $request->tanggal
            );

            try {
                if (str_contains($raw, ' s.d ')) {
                    [$startRaw, $endRaw] = explode(
                        ' s.d ',
                        $raw,
                        2
                    );

                    $start = Carbon::parse(
                        $startRaw
                    )->startOfDay();

                    $end = Carbon::parse(
                        $endRaw
                    )->endOfDay();
                } else {
                    $start = Carbon::parse(
                        $raw
                    )->startOfDay();

                    $end = Carbon::parse(
                        $raw
                    )->endOfDay();
                }

                if ($end->lt($start)) {
                    [$start, $end] = [
                        $end,
                        $start,
                    ];
                }

                $query->whereBetween(
                    'tanggal',
                    [
                        $start->toDateString(),
                        $end->toDateString(),
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning(
                    'Filter tanggal packing gagal.',
                    [
                        'tanggal' => $raw,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        $pesanan = $query->get();

        $jumlahPesanan = $pesanan->count();

        $hariIni = Pesanan::whereDate(
            'tanggal_kirim',
            today()
        )
            ->where(
                'id_user_kirim',
                Auth::id()
            )
            ->count();

        $kurirHariIni =
            $this->getKurirHariIni();

        return view(
            'packing.pesanan',
            compact(
                'pesanan',
                'jumlahPesanan',
                'hariIni',
                'kurirHariIni'
            )
        );
    }

    public function scan(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => '❌ Auth error',
            ], 401);
        }

        $request->validate([
            'no_pesanan' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $kode = preg_replace(
            '/\s+/',
            '',
            trim(
                (string) $request->no_pesanan
            )
        );

        $pesanan = Pesanan::with('toko')
            ->where(
                'status',
                'proses'
            )
            ->where(function ($q) use ($kode) {
                $q->where(
                    'no_resi',
                    $kode
                )
                    ->orWhere(
                        'no_pesanan',
                        $kode
                    );
            })
            ->first();

        if (!$pesanan) {
            $pesananLama = Pesanan::where(
                function ($q) use ($kode) {
                    $q->where(
                        'no_resi',
                        $kode
                    )
                        ->orWhere(
                            'no_pesanan',
                            $kode
                        );
                }
            )->first();

            if ($pesananLama) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        "⚠ Pesanan {$pesananLama->no_pesanan} sudah berstatus {$pesananLama->status}",
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' =>
                    '❌ No. Pesanan / Resi tidak ditemukan',
            ], 404);
        }

        DB::transaction(
            function () use ($pesanan) {
                $pesanan->status =
                    'kirim';

                $pesanan->id_user_kirim =
                    Auth::id();

                $pesanan->tanggal_kirim =
                    now();

                $pesanan->save();
            }
        );

        $ekspedisi =
            $this->detectEkspedisi(
                $pesanan->kurir
            );

        $sisaProses = Pesanan::where(
            'status',
            'proses'
        )->count();

        $hariIni = Pesanan::whereDate(
            'tanggal_kirim',
            today()
        )
            ->where(
                'id_user_kirim',
                Auth::id()
            )
            ->count();

        $kurirHariIni =
            $this->getKurirHariIni();

        return response()->json([
            'success' => true,

            'message' =>
                "✅ {$pesanan->no_pesanan} berhasil dikirim",

            'no_pesanan' =>
                $pesanan->no_pesanan,

            'no_resi' =>
                $pesanan->no_resi,

            'scan_time' =>
                $pesanan
                    ->tanggal_kirim
                    ->format('H:i:s'),

            'status' =>
                'kirim',

            'ekspedisi' =>
                $ekspedisi,

            'remaining_proses' =>
                $sisaProses,

            'hari_ini' =>
                $hariIni,

            'kurir_hari_ini' => [
                'spx' =>
                    (int) (
                        $kurirHariIni->spx ?? 0
                    ),

                'jnt' =>
                    (int) (
                        $kurirHariIni->jnt ?? 0
                    ),

                'anteraja' =>
                    (int) (
                        $kurirHariIni->anteraja ?? 0
                    ),

                'jne' =>
                    (int) (
                        $kurirHariIni->jne ?? 0
                    ),
            ],
        ]);
    }

    public function stats()
    {
        $hariIni = Pesanan::whereDate(
            'tanggal_kirim',
            today()
        )
            ->where(
                'id_user_kirim',
                Auth::id()
            )
            ->count();

        $totalProses = Pesanan::where(
            'status',
            'proses'
        )->count();

        $kurirHariIni =
            $this->getKurirHariIni();

        return response()->json([
            'hari_ini' =>
                $hariIni,

            'total' =>
                $totalProses,

            'kurir_hari_ini' => [
                'spx' =>
                    (int) (
                        $kurirHariIni->spx ?? 0
                    ),

                'jnt' =>
                    (int) (
                        $kurirHariIni->jnt ?? 0
                    ),

                'anteraja' =>
                    (int) (
                        $kurirHariIni->anteraja ?? 0
                    ),

                'jne' =>
                    (int) (
                        $kurirHariIni->jne ?? 0
                    ),
            ],
        ]);
    }

    public function cetakIndex()
    {
        return view(
            'packing.cetak-resi'
        );
    }
    public function cariRequest(Request $request)
    {
        $request->validate([
            'no_pesanan' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $noPesanan = preg_replace(
            '/\s+/',
            '',
            trim((string) $request->no_pesanan)
        );

        $pesanan = $this->findPesananUntukCetak(
            $noPesanan
        );

        if (!$pesanan) {
            $pesananLama = Pesanan::select([
                'no_pesanan',
                'status',
            ])
                ->where(
                    'no_pesanan',
                    $noPesanan
                )
                ->first();

            if ($pesananLama) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        "Pesanan {$pesananLama->no_pesanan} sudah berstatus {$pesananLama->status}.",
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' =>
                    "Hasil scan QR {$noPesanan} tidak ditemukan.",
            ], 404);
        }

        return $this->responseDetailPesanan(
            $pesanan
        );
    }
    private function findPesananUntukCetak(
        string $noPesanan
    ): ?Pesanan {
        return Pesanan::with([
            'toko',
            'resiPages.import',
            'resiPrinter',
        ])
            ->where(
                'no_pesanan',
                $noPesanan
            )
            ->where(
                'status',
                'proses'
            )
            ->whereHas(
                'toko',
                function ($q) {
                    $q->whereIn(
                        'marketplace',
                        self::PACKING_MARKETPLACES
                    );
                }
            )
            ->first();
    }

    private function responseDetailPesanan(
        Pesanan $pesanan
    ) {
        $pesanan->loadMissing([
            'toko',
            'resiPages.import',
            'resiPrinter',
        ]);

        $marketplace = (string) (
            $pesanan
                ->toko
                ?->marketplace ?? ''
        );

        $resiPages = $this->getResiPages(
            $pesanan
        );

        return response()->json([
            'success' => true,
            'multiple' => false,
            'requests' =>
                $this->buildRequestPayload(
                    $pesanan
                ),
            'pesanan' => [
                'no_pesanan' =>
                    $pesanan
                        ->no_pesanan,
                'no_resi' =>
                    $pesanan
                        ->no_resi,
                'nama_pembeli' =>
                    $pesanan
                        ->nama_pembeli,
                'marketplace' =>
                    $marketplace,
                'produk' =>
                    $this->buildProdukPayload(
                        $pesanan
                    ),
                'pdf_tersedia' =>
                    $resiPages
                        ->isNotEmpty(),
                'jumlah_halaman_resi' =>
                    $resiPages
                        ->count(),
                'sudah_print' =>
                    !is_null(
                        $pesanan
                            ->resi_printed_at
                    ),
                'print_count' =>
                    (int) (
                        $pesanan
                            ->resi_print_count ?? 0
                    ),
                'first_printed_at' =>
                    $pesanan
                        ->resi_printed_at
                        ? Carbon::parse(
                            $pesanan
                                ->resi_printed_at
                        )->format(
                            'd/m/Y H:i:s'
                        )
                        : null,
                'first_printed_by' =>
                    $pesanan
                        ->resiPrinter
                        ? $pesanan
                            ->resiPrinter
                            ->name
                        : null,
            ],
        ]);
    }
    private function buildProdukPayload(
        Pesanan $pesanan
    ) {
        return DB::table('pesanan_per_produk as pp')
            ->leftJoin(
                'produk as p',
                'p.sku',
                '=',
                'pp.sku'
            )
            ->where(
                'pp.no_pesanan',
                $pesanan->no_pesanan
            )
            ->select([
                'pp.id_per_produk',
                'pp.sku',
                'pp.jumlah',
                'p.nama_produk',
                'p.variasi',
            ])
            ->orderBy(
                'pp.id_per_produk'
            )
            ->get()
            ->map(function ($item) {
                return [
                    'id_per_produk' =>
                        $item->id_per_produk,
                    'nama_produk' =>
                        $item->nama_produk ?? '-',
                    'variasi' =>
                        $item->variasi ?? '-',
                    'sku' =>
                        $item->sku,
                    'jumlah' =>
                        (int) $item->jumlah,
                ];
            })
            ->values();
    }
    private function buildRequestPayload(
        Pesanan $pesanan
    ) {
        return DB::table('editor_requests as er')
            ->join(
                'pesanan_per_produk as pp',
                'pp.id_per_produk',
                '=',
                'er.id_per_produk'
            )
            ->where(
                'pp.no_pesanan',
                $pesanan->no_pesanan
            )
            ->whereNotNull(
                'er.locked_at'
            )
            ->whereIn(
                'er.status_request',
                [
                    'normal',
                    'random',
                ]
            )
            ->select([
                'er.id',
                'er.id_per_produk',
                'pp.sku',
                'er.plat_lengkap',
                'er.nama',
                'er.tanggal_bulan_tahun',
                'er.jumlah_editor',
                'er.status_request',
                'er.request_search',
            ])
            ->orderBy(
                'er.id'
            )
            ->get()
            ->map(function ($editor) {
                return [
                    'id' =>
                        $editor->id,
                    'id_per_produk' =>
                        $editor->id_per_produk,
                    'sku' =>
                        $editor->sku,
                    'plat_lengkap' =>
                        $editor->plat_lengkap,
                    'nama' =>
                        $editor->nama,
                    'tanggal_bulan_tahun' =>
                        $editor->tanggal_bulan_tahun,
                    'jumlah' =>
                        (int) $editor->jumlah_editor,
                    'status_request' =>
                        $editor->status_request,
                    'request_search' =>
                        $editor->request_search,
                ];
            })
            ->values();
    }

    private function getResiPages(
        Pesanan $pesanan
    ) {
        return $pesanan
            ->resiPages
            ->filter(
                function ($page) {
                    return $page->import !== null;
                }
            )
            ->sortBy([
                [
                    'urutan',
                    'asc',
                ],
                [
                    'halaman',
                    'asc',
                ],
            ])
            ->values();
    }

    public function cetakResi(
        Request $request
    ) {
        $request->validate([
            'no_pesanan' => [
                'required',
                'string',
                'max:50',
            ],

            'allow_reprint' => [
                'nullable',
                'boolean',
            ],
        ]);

        $pesanan = Pesanan::with([
            'resiPages.import',
            'resiPrinter',
            'toko',
        ])
            ->where(
                'no_pesanan',
                $request->no_pesanan
            )
            ->where(
                'status',
                'proses'
            )
            ->whereHas(
                'toko',
                function ($q) {
                    $q->whereIn(
                        'marketplace',
                        self::PACKING_MARKETPLACES
                    );
                }
            )
            ->first();

        if (!$pesanan) {
            return back()->with(
                'error',
                'Pesanan tidak ditemukan atau sudah tidak berstatus proses.'
            );
        }

        if (
            $pesanan->resi_printed_at &&
            !$request->boolean(
                'allow_reprint'
            )
        ) {
            return back()->with(
                'error',
                'Resi sudah pernah dicetak. Konfirmasi cetak ulang diperlukan.'
            );
        }

        $marketplace =
            (string) (
                $pesanan
                    ->toko
                    ?->marketplace ?? ''
            );

        $pages =
            $this->getResiPages(
                $pesanan
            );

        if ($pages->isEmpty()) {
            return back()->with(
                'error',
                'PDF resi untuk pesanan ini belum tersedia.'
            );
        }

        $requestLines =
            $this->getRequestPdfLines(
                $pesanan
            );

        $tempDirectory =
            storage_path(
                'app/private/print_temp'
            );

        File::ensureDirectoryExists(
            $tempDirectory
        );

        $safeNoPesanan =
            preg_replace(
                '/[^A-Za-z0-9\-_]/',
                '_',
                (string) $pesanan
                    ->no_pesanan
            );

        $tempPath =
            $tempDirectory .
            DIRECTORY_SEPARATOR .
            'resi_' .
            $safeNoPesanan .
            '_' .
            uniqid() .
            '.pdf';

        $preparedSources = [];

        try {
            $pdf = new Fpdi();

            $pdf->SetAutoPageBreak(
                false
            );

            foreach ($pages as $page) {
                if (!$page->import) {
                    throw new \Exception(
                        "Data import PDF halaman {$page->halaman} tidak ditemukan."
                    );
                }

                $sourcePath = storage_path(
                    'app/private/' .
                    ltrim(
                        $page->import->path_file,
                        '/'
                    )
                );

                if (!File::exists($sourcePath)) {
                    throw new \Exception(
                        'File PDF sumber tidak ditemukan.'
                    );
                }

                if (!isset(
                    $preparedSources[$sourcePath]
                )) {
                    $preparedSources[$sourcePath] =
                        $this->preparePdfForFpdi(
                            $sourcePath,
                            $marketplace,
                            $tempDirectory
                        );
                }

                $preparedPdf =
                    $preparedSources[$sourcePath];

                $pdf->setSourceFile(
                    $preparedPdf['path']
                );

                $template =
                    $pdf->importPage(
                        (int) $page->halaman
                    );

                $size =
                    $pdf->getTemplateSize(
                        $template
                    );

                $pdf->AddPage(
                    $size['orientation'],
                    [
                        $size['width'],
                        $size['height'],
                    ]
                );

                $pdf->useTemplate(
                    $template
                );

                if (!empty($requestLines)) {
                    $this->drawRequestOnPdf(
                        $pdf,
                        $size,
                        $requestLines,
                        $marketplace
                    );
                }
            }

            $pdf->Output(
                'F',
                $tempPath
            );

            $this->cleanupPreparedPdfs(
                $preparedSources
            );

            DB::transaction(
                function () use ($pesanan) {
                    $lockedPesanan =
                        Pesanan::where(
                            'no_pesanan',
                            $pesanan
                                ->no_pesanan
                        )
                            ->lockForUpdate()
                            ->first();

                    if (!$lockedPesanan) {
                        throw new \Exception(
                            'Pesanan tidak ditemukan.'
                        );
                    }

                    $now =
                        now();

                    if (
                        !$lockedPesanan
                            ->resi_printed_at
                    ) {
                        $lockedPesanan
                            ->resi_printed_at =
                            $now;

                        $lockedPesanan
                            ->resi_printed_by =
                            Auth::id();
                    }

                    $lockedPesanan
                        ->resi_last_printed_at =
                        $now;

                    $lockedPesanan
                        ->resi_print_count =
                        (
                            (int)
                            $lockedPesanan
                                ->resi_print_count
                        ) + 1;

                    $lockedPesanan->save();
                }
            );

            return response()
                ->file(
                    $tempPath,
                    [
                        'Content-Type' =>
                            'application/pdf',

                        'Content-Disposition' =>
                            'inline; filename="RESI_' .
                            $safeNoPesanan .
                            '.pdf"',
                    ]
                )
                ->deleteFileAfterSend(
                    true
                );

        } catch (\Throwable $e) {
            File::delete(
                $tempPath
            );

            $this->cleanupPreparedPdfs(
                $preparedSources
            );

            Log::error(
                'Cetak resi packing gagal.',
                [
                    'marketplace' =>
                        $marketplace,

                    'no_pesanan' =>
                        $pesanan->no_pesanan,

                    'error' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ]
            );

            return response(
                '
                <div style="
                    font-family:Arial,sans-serif;
                    max-width:800px;
                    margin:40px auto;
                    padding:30px;
                ">
                    <h2 style="color:#dc3545;">
                        Gagal Cetak Resi
                    </h2>

                    <p>
                        <strong>Marketplace:</strong>
                        ' . e($marketplace) . '
                    </p>

                    <p>
                        <strong>No. Pesanan:</strong>
                        ' . e($pesanan->no_pesanan) . '
                    </p>

                    <p>
                        <strong>Error:</strong>
                        ' . e($e->getMessage()) . '
                    </p>
                </div>
                ',
                500
            );
        }
    }
    private function getRequestPdfLines(
        Pesanan $pesanan
    ): array {
        return DB::table('editor_requests as er')
            ->join(
                'pesanan_per_produk as pp',
                'pp.id_per_produk',
                '=',
                'er.id_per_produk'
            )
            ->where(
                'pp.no_pesanan',
                $pesanan->no_pesanan
            )
            ->whereNotNull(
                'er.locked_at'
            )
            ->whereIn(
                'er.status_request',
                [
                    'normal',
                    'random',
                ]
            )
            ->select([
                'er.plat_lengkap',
                'er.nama',
                'er.tanggal_bulan_tahun',
                'er.status_request',
            ])
            ->orderBy(
                'er.id'
            )
            ->get()
            ->map(function ($editor) {
                if (
                    $editor->status_request ===
                    'random'
                ) {
                    return 'RANDOM';
                }

                $parts = [];

                if ($editor->plat_lengkap) {
                    $parts[] = trim(
                        $editor->plat_lengkap
                    );
                }

                if ($editor->nama) {
                    $parts[] = trim(
                        $editor->nama
                    );
                }

                if ($editor->tanggal_bulan_tahun) {
                    $parts[] = trim(
                        $editor
                            ->tanggal_bulan_tahun
                    );
                }

                if (empty($parts)) {
                    return null;
                }

                return implode(
                    ' | ',
                    $parts
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    private function drawRequestOnPdf(
        Fpdi $pdf,
        array $size,
        array $requestLines,
        string $marketplace
    ): void {
        if (empty($requestLines)) {
            return;
        }

        if (
            strcasecmp(
                $marketplace,
                'Tiktok'
            ) === 0
        ) {
            $this->drawTikTokRequestOnPdf(
                $pdf,
                $size,
                $requestLines
            );

            return;
        }

        $this->drawShopeeRequestOnPdf(
            $pdf,
            $size,
            $requestLines
        );
    }

    private function drawShopeeRequestOnPdf(
        Fpdi $pdf,
        array $size,
        array $requestLines
    ): void {
        $pageWidth =
            (float) $size['width'];

        $pageHeight =
            (float) $size['height'];

        $marginX = 6;

        $contentWidth =
            $pageWidth -
            ($marginX * 2);

        $jumlahRequest =
            count($requestLines);

        if ($jumlahRequest === 1) {
            $fontSize = 25;
            $lineHeight = 10;
            $marginPerRequest = 4;

        } elseif ($jumlahRequest === 2) {
            $fontSize = 18;
            $lineHeight = 8;
            $marginPerRequest = 4;

        } elseif ($jumlahRequest === 3) {
            $fontSize = 15;
            $lineHeight = 7;
            $marginPerRequest = 3;

        } else {
            $fontSize = 12;
            $lineHeight = 6;
            $marginPerRequest = 2;
        }

        $startY =
            $pageHeight *
            0.80;

        $pdf->SetTextColor(
            0,
            0,
            0
        );

        $pdf->SetFont(
            'Arial',
            'B',
            $fontSize
        );

        foreach ($requestLines as $line) {
            $pdf->SetXY(
                $marginX,
                $startY
            );

            $pdf->MultiCell(
                $contentWidth,
                $lineHeight,
                $this->pdfText(
                    $line
                ),
                0,
                'C'
            );

            $startY =
                $pdf->GetY() +
                $marginPerRequest;
        }
    }

    private function drawTikTokRequestOnPdf(
        Fpdi $pdf,
        array $size,
        array $requestLines
    ): void {
        $pageWidth = (float) $size['width'];
        $pageHeight = (float) $size['height'];

        $marginX = 6;
        $contentWidth = $pageWidth - ($marginX * 2);
        $jumlahRequest = count($requestLines);

        if ($jumlahRequest === 1) {
            $fontSize = 25;
            $lineHeight = 10;
            $marginPerRequest = 4;
        } elseif ($jumlahRequest === 2) {
            $fontSize = 18;
            $lineHeight = 8;
            $marginPerRequest = 4;
        } elseif ($jumlahRequest === 3) {
            $fontSize = 15;
            $lineHeight = 7;
            $marginPerRequest = 3;
        } else {
            $fontSize = 12;
            $lineHeight = 6;
            $marginPerRequest = 2;
        }

        $startY = $pageHeight * 0.78;

        $pdf->SetTextColor(
            0,
            0,
            0
        );

        $pdf->SetFont(
            'Arial',
            'B',
            $fontSize
        );

        foreach ($requestLines as $line) {
            $pdf->SetXY(
                $marginX,
                $startY
            );

            $pdf->MultiCell(
                $contentWidth,
                $lineHeight,
                $this->pdfText($line),
                0,
                'C'
            );

            $startY =
                $pdf->GetY() +
                $marginPerRequest;
        }
    }

    private function preparePdfForFpdi(
        string $sourcePath,
        string $marketplace,
        string $tempDirectory
    ): array {
        if (strcasecmp($marketplace, 'Tiktok') !== 0) {
            return [
                'path' => $sourcePath,
                'temporary' => false,
            ];
        }

        $normalizedPath =
            $tempDirectory .
            DIRECTORY_SEPARATOR .
            'normalized_' .
            uniqid('', true) .
            '.pdf';

        $qpdf = config(
            'services.qpdf.path',
            'qpdf'
        );

        $command =
            escapeshellarg($qpdf) .
            ' --object-streams=disable ' .
            escapeshellarg($sourcePath) .
            ' ' .
            escapeshellarg($normalizedPath) .
            ' 2>&1';

        $output = [];
        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if (
            $exitCode !== 0 ||
            !File::exists($normalizedPath)
        ) {
            File::delete(
                $normalizedPath
            );

            throw new \Exception(
                'Gagal memproses PDF TikTok dengan qpdf. ' .
                implode(' ', $output)
            );
        }

        return [
            'path' => $normalizedPath,
            'temporary' => true,
        ];
    }

    private function cleanupPreparedPdfs(
        array $preparedSources
    ): void {
        foreach (
            $preparedSources
            as $preparedPdf
        ) {
            if (
                ($preparedPdf['temporary'] ?? false) &&
                !empty($preparedPdf['path']) &&
                File::exists(
                    $preparedPdf['path']
                )
            ) {
                File::delete(
                    $preparedPdf['path']
                );
            }
        }
    }

    private function pdfText(
        ?string $text
    ): string {
        $converted =
            @iconv(
                'UTF-8',
                'windows-1252//TRANSLIT',
                (string) $text
            );

        return $converted !== false
            ? $converted
            : (string) $text;
    }

    private function normalizeRequestSearch(
        ?string $value
    ): ?string {
        $value =
            mb_strtoupper(
                trim(
                    (string) $value
                )
            );

        $value =
            preg_replace(
                '/[^\p{L}\p{N}]+/u',
                '',
                $value
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function getKurirHariIni()
    {
        return Pesanan::whereDate(
            'tanggal_kirim',
            today()
        )
            ->where(
                'id_user_kirim',
                Auth::id()
            )
            ->selectRaw("
                SUM(
                    CASE
                        WHEN LOWER(kurir) LIKE '%spx%'
                        THEN 1
                        ELSE 0
                    END
                ) AS spx,

                SUM(
                    CASE
                        WHEN LOWER(kurir) LIKE '%j&t%'
                            OR LOWER(kurir) LIKE '%jnt%'
                        THEN 1
                        ELSE 0
                    END
                ) AS jnt,

                SUM(
                    CASE
                        WHEN LOWER(kurir) LIKE '%anteraja%'
                        THEN 1
                        ELSE 0
                    END
                ) AS anteraja,

                SUM(
                    CASE
                        WHEN LOWER(kurir) LIKE '%jne%'
                        THEN 1
                        ELSE 0
                    END
                ) AS jne
            ")
            ->first();
    }

    private function detectEkspedisi(
        ?string $kurir
    ): string {
        $kurir =
            strtolower(
                trim(
                    $kurir ?? ''
                )
            );

        if (
            str_contains(
                $kurir,
                'spx'
            )
        ) {
            return 'spx';
        }

        if (
            str_contains(
                $kurir,
                'j&t'
            ) ||
            str_contains(
                $kurir,
                'jnt'
            )
        ) {
            return 'jnt';
        }

        if (
            str_contains(
                $kurir,
                'anteraja'
            )
        ) {
            return 'anteraja';
        }

        if (
            str_contains(
                $kurir,
                'jne'
            )
        ) {
            return 'jne';
        }

        return 'unknown';
    }
}

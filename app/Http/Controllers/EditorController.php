<?php

namespace App\Http\Controllers;

use App\Models\EditorPart;
use App\Models\EditorPartItem;
use App\Models\EditorRequest;
use App\Services\EditorPartService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;

class EditorController extends Controller
{
    public function index()
    {
        $totalPartAktif = EditorPart::whereIn(
        'status',
        ['open', 'downloaded']
    )
        ->whereHas(
            'items',
            function ($q) {
                $q->where(
                    'status',
                    'pending'
                );
            }
        )
        ->count();

        $totalBelumEditor = EditorPartItem::where(
            'status',
            'pending'
        )
            ->whereHas('part', function ($q) {
                $q->whereIn(
                    'status',
                    ['open', 'downloaded']
                );
            })
            ->count();

        $totalSelesaiEditor = EditorPartItem::where(
            'status',
            'locked'
        )->count();

        $totalMenunggu = $this
            ->queryMenunggu()
            ->count();

        $partsTerbaru = EditorPart::withCount([
            'items as jumlah_item',
            'items as pending_count' => fn ($q) =>
                $q->where('status', 'pending'),
            'items as locked_count' => fn ($q) =>
                $q->where('status', 'locked'),
            'items as skipped_count' => fn ($q) =>
                $q->where('status', 'skipped'),
        ])
            ->orderByDesc('tanggal_part')
            ->orderByDesc('nomor_part')
            ->limit(5)
            ->get();

        return view('editor.index', compact(
            'totalPartAktif',
            'totalBelumEditor',
            'totalSelesaiEditor',
            'totalMenunggu',
            'partsTerbaru'
        ));
    }

    public function partIndex(
    EditorPartService $partService
    ) {
        try {
            $partService->sinkronkanPekerjaanTersedia(
                Auth::id()
            );

            EditorPart::where(
                'status',
                'open'
            )
                ->whereDoesntHave('items')
                ->delete();

        } catch (\Throwable $e) {
            report($e);

            session()->flash(
                'error',
                'Sinkronisasi pekerjaan Editor gagal: ' .
                $e->getMessage()
            );
        }

        $parts = EditorPart::with([
            'items' => fn ($q) =>
                $q->orderBy('urutan'),
            'items.item.pesanan',
        ])
            ->withCount([
                'items as jumlah_item',

                'items as pending_count' => fn ($q) =>
                    $q->where(
                        'status',
                        'pending'
                    ),

                'items as locked_count' => fn ($q) =>
                    $q->where(
                        'status',
                        'locked'
                    ),

                'items as skipped_count' => fn ($q) =>
                    $q->where(
                        'status',
                        'skipped'
                    ),
            ])
            ->whereIn(
                'status',
                [
                    'open',
                    'downloaded',
                ]
            )
            ->whereHas(
                'items',
                function ($q) {
                    $q->where(
                        'status',
                        'pending'
                    );
                }
            )
            ->orderByDesc(
                'tanggal_part'
            )
            ->orderBy(
                'nomor_part'
            )
            ->paginate(20);

        return view(
            'editor.part.index',
            compact('parts')
        );
    }

    public function partShow(
        EditorPart $part
    ) {
        $part->load([
            'items' => fn ($q) =>
                $q->orderBy('urutan'),
            'items.item.pesanan',
        ]);

        $kelompok = $part->items
            ->where('status', '!=', 'skipped')
            ->groupBy('kelompok_produksi')
            ->map(function ($items) {
                return [
                    'jumlah' => $items->sum(
                        fn ($item) =>
                            (int) $item->jumlah_awal
                    ),
                    'item' => $items->count(),
                ];
            });

        return view(
            'editor.part.show',
            compact(
                'part',
                'kelompok'
            )
        );
    }

    public function downloadPlat(
        EditorPart $part
    ) {
        try {
            if (!in_array(
                $part->status,
                ['open', 'downloaded'],
                true
            )) {
                return back()->with(
                    'error',
                    'Part ini sudah selesai diproses.'
                );
            }

            $templatePath = storage_path(
                'app/templates/editor_plat.xlsx'
            );

            if (!file_exists($templatePath)) {
                return back()->with(
                    'error',
                    'Template editor_plat.xlsx tidak ditemukan.'
                );
            }

            $spreadsheet = IOFactory::load(
                $templatePath
            );

            $sheet = $spreadsheet
                ->getSheetByName(
                    'PLAT'
                );

            if (!$sheet) {
                $spreadsheet
                    ->disconnectWorksheets();

                return back()->with(
                    'error',
                    'Sheet PLAT tidak ditemukan pada template.'
                );
            }

            DB::transaction(
                function () use ($part) {
                    $lockedPart =
                        EditorPart::where(
                            'id',
                            $part->id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (!in_array(
                        $lockedPart->status,
                        ['open', 'downloaded'],
                        true
                    )) {
                        throw new \Exception(
                            'Part ini sudah selesai diproses.'
                        );
                    }

                    $adaPending =
                        EditorPartItem::where(
                            'editor_part_id',
                            $lockedPart->id
                        )
                            ->where(
                                'status',
                                'pending'
                            )
                            ->exists();

                    if (!$adaPending) {
                        throw new \Exception(
                            'Tidak ada item pending pada Part ini.'
                        );
                    }

                    if (
                        $lockedPart->status ===
                        'open'
                    ) {
                        $lockedPart->update([
                            'status' =>
                                'downloaded',

                            'downloaded_by' =>
                                Auth::id(),

                            'downloaded_at' =>
                                now(),
                        ]);
                    }
                }
            );

            $part->refresh();

            $part->load([
                'items' => function ($q) {
                    $q->where(
                        'status',
                        'pending'
                    )
                        ->orderBy(
                            'urutan'
                        );
                },
                'items.item.pesanan',
            ]);

            if ($part->items->isEmpty()) {
                $spreadsheet->disconnectWorksheets();

                return back()->with(
                    'error',
                    'Tidak ada item pending pada Part ini.'
                );
            }

            $sheet->setCellValue(
                'A1',
                'SKU'
            );

            $sheet->setCellValue(
                'B1',
                'PLAT LENGKAP'
            );

            $sheet->setCellValue(
                'C1',
                'NAMA'
            );

            $sheet->setCellValue(
                'D1',
                'TANGGAL/BULAN TAHUN'
            );

            $sheet->setCellValue(
                'E1',
                'JUMLAH'
            );

            $sheet->setCellValue(
                'F1',
                'TANPA HEARTBEAT'
            );

            $sheet->setCellValue(
                'G1',
                'ID ITEM'
            );

            $sheet->setCellValue(
                'H1',
                'NO PESANAN'
            );

            $sheet->setCellValue(
                'I1',
                'STATUS REQUEST'
            );

            $sheet->setCellValue(
                'J1',
                'BATAS KIRIM'
            );

            $highestRow = max(
                2,
                $sheet->getHighestDataRow()
            );

            for (
                $row = 2;
                $row <= $highestRow;
                $row++
            ) {
                for (
                    $column = 'A';
                    $column <= 'J';
                    $column++
                ) {
                    $sheet
                        ->getCell(
                            $column . $row
                        )
                        ->setValue(null);
                }
            }

            $row = 2;

            foreach (
                $part->items
                as $partItem
            ) {
                $item =
                    $partItem->item;

                if (
                    !$item ||
                    !$item->pesanan
                ) {
                    continue;
                }

                $sheet->setCellValueExplicit(
                    'A' . $row,
                    (string) $item->sku,
                    DataType::TYPE_STRING
                );

                $sheet->setCellValue(
                    'B' . $row,
                    ''
                );

                $sheet->setCellValue(
                    'C' . $row,
                    ''
                );

                $sheet->setCellValue(
                    'D' . $row,
                    ''
                );

                $sheet->setCellValue(
                    'E' . $row,
                    (int) $item->jumlah
                );

                $sheet->setCellValue(
                    'F' . $row,
                    ''
                );

                $sheet->setCellValueExplicit(
                    'G' . $row,
                    (string)
                    $item->id_per_produk,
                    DataType::TYPE_STRING
                );

                $sheet->setCellValueExplicit(
                    'H' . $row,
                    (string)
                    $item->no_pesanan,
                    DataType::TYPE_STRING
                );

                $sheet->setCellValue(
                    'I' . $row,
                    ''
                );

                $sheet->setCellValue(
                    'J' . $row,
                    $this->formatBatasKirim(
                        $item
                            ->pesanan
                            ->batas_kirim_at
                    )
                );

                $row++;
            }

            $lastRow =
                $row - 1;

            if ($lastRow >= 2) {
                $sheet
                    ->getStyle(
                        'A2:A' . $lastRow
                    )
                    ->getNumberFormat()
                    ->setFormatCode('@');

                $sheet
                    ->getStyle(
                        'G2:H' . $lastRow
                    )
                    ->getNumberFormat()
                    ->setFormatCode('@');
            }

            $this->buatMetaSheet(
                $spreadsheet,
                $part
            );

            $filename =
                'EDITOR_' .
                $part->kode_part .
                '.xlsx';

            return response()->streamDownload(
                function () use (
                    $spreadsheet
                ) {
                    $writer =
                        new Xlsx(
                            $spreadsheet
                        );

                    $writer->save(
                        'php://output'
                    );

                    $spreadsheet
                        ->disconnectWorksheets();
                },
                $filename,
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                    'Cache-Control' =>
                        'max-age=0, no-cache, no-store, must-revalidate',
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function importPage()
    {
        return view(
            'editor.import'
        );
    }

    public function importEditor(Request $request)
    {
        $request->validate([
            'file_editor' => [
                'required',
                'file',
                'mimes:xlsx,xls,xlsm',
                'max:20480',
            ],
        ]);

        $spreadsheet = null;

        try {
            $spreadsheet = IOFactory::load(
                $request->file('file_editor')->getRealPath()
            );

            $sheet = $spreadsheet->getSheetByName('PLAT');

            if (!$sheet) {
                $spreadsheet->disconnectWorksheets();

                return back()->with(
                    'error',
                    'Sheet PLAT tidak ditemukan.'
                );
            }

            $part = $this->ambilPartDariExcel(
                $spreadsheet
            );

            if (!$part) {
                $spreadsheet->disconnectWorksheets();

                return back()->with(
                    'error',
                    'Informasi Part tidak ditemukan pada file Excel.'
                );
            }

            if ($part->status === 'processed') {
                $spreadsheet->disconnectWorksheets();

                return redirect()
                    ->route('editor.riwayat.index')
                    ->with(
                        'success',
                        "Part {$part->kode_part} sudah berhasil diproses."
                    );
            }

            if ($part->status !== 'downloaded') {
                $spreadsheet->disconnectWorksheets();

                return back()->with(
                    'error',
                    "Part {$part->kode_part} belum didownload atau sudah tidak dapat diproses."
                );
            }

            $this->validasiHeader($sheet);

            $partItems = EditorPartItem::with([
                'item.pesanan',
            ])
                ->where(
                    'editor_part_id',
                    $part->id
                )
                ->where(
                    'status',
                    'pending'
                )
                ->orderBy(
                    'urutan'
                )
                ->get()
                ->keyBy(
                    fn ($item) =>
                        (string) $item->id_per_produk
                );

            if ($partItems->isEmpty()) {
                $spreadsheet->disconnectWorksheets();

                return redirect()
                    ->route('editor.riwayat.index')
                    ->with(
                        'success',
                        "Part {$part->kode_part} sudah tidak memiliki item pending."
                    );
            }

            $highestRow =
                $sheet->getHighestDataRow();

            $groupedRows = [];
            $invalidItemIds = [];
            $errors = [];

            for (
                $row = 2;
                $row <= $highestRow;
                $row++
            ) {
                $sku = $this->nullableText(
                    $sheet
                        ->getCell("A{$row}")
                        ->getFormattedValue()
                );

                $platLengkap = $this->nullableText(
                    $sheet
                        ->getCell("B{$row}")
                        ->getFormattedValue()
                );

                $nama = $this->nullableText(
                    $sheet
                        ->getCell("C{$row}")
                        ->getFormattedValue()
                );

                $tanggalBulanTahun = $this->nullableText(
                    $sheet
                        ->getCell("D{$row}")
                        ->getFormattedValue()
                );

                $jumlah = $this->nullableText(
                    $sheet
                        ->getCell("E{$row}")
                        ->getFormattedValue()
                );

                $tanpaHeartbeat = $this->nullableText(
                    $sheet
                        ->getCell("F{$row}")
                        ->getFormattedValue()
                );

                $idItem = $this->nullableText(
                    $sheet
                        ->getCell("G{$row}")
                        ->getFormattedValue()
                );

                $noPesanan = $this->nullableText(
                    $sheet
                        ->getCell("H{$row}")
                        ->getFormattedValue()
                );

                $statusRaw = $this->nullableText(
                    $sheet
                        ->getCell("I{$row}")
                        ->getFormattedValue()
                );

                if (
                    $sku === null &&
                    $platLengkap === null &&
                    $nama === null &&
                    $tanggalBulanTahun === null &&
                    $jumlah === null &&
                    $tanpaHeartbeat === null &&
                    $idItem === null &&
                    $noPesanan === null &&
                    $statusRaw === null
                ) {
                    continue;
                }

                if ($idItem === null) {
                    $errors[] =
                        "Baris {$row}: ID ITEM kosong.";

                    continue;
                }

                $idItem = trim(
                    (string) $idItem
                );

                if (!$partItems->has($idItem)) {
                    $errors[] =
                        "Baris {$row}: ID ITEM {$idItem} bukan item pending dari {$part->kode_part}.";

                    continue;
                }

                $partItem =
                    $partItems->get($idItem);

                $item =
                    $partItem->item;

                if (!$item) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: ID ITEM {$idItem} tidak ditemukan.";

                    continue;
                }

                if (!$item->pesanan) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: pesanan untuk ID ITEM {$idItem} tidak ditemukan.";

                    continue;
                }

                if (
                    $item->pesanan->status !==
                    'proses'
                ) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: pesanan {$item->no_pesanan} sudah tidak berstatus proses.";

                    continue;
                }

                if ($noPesanan === null) {
                    $noPesanan =
                        (string) $item->no_pesanan;
                }

                if (
                    (string) $item->no_pesanan !==
                    (string) $noPesanan
                ) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: NO PESANAN {$noPesanan} tidak cocok dengan ID ITEM {$idItem}.";

                    continue;
                }

                if (
                    $sku !== null &&
                    strtoupper(
                        trim(
                            (string) $item->sku
                        )
                    ) !==
                    strtoupper(
                        trim(
                            (string) $sku
                        )
                    )
                ) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: SKU {$sku} tidak cocok dengan ID ITEM {$idItem}.";

                    continue;
                }

                if (
                    $jumlah === null ||
                    !is_numeric($jumlah) ||
                    (int) $jumlah < 1
                ) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: JUMLAH harus minimal 1.";

                    continue;
                }

                $statusRequest =
                    $this->normalizeStatusRequest(
                        $statusRaw
                    );

                if ($statusRequest === null) {
                    $invalidItemIds[$idItem] = true;

                    $errors[] =
                        "Baris {$row}: STATUS REQUEST hanya boleh kosong, NORMAL, MENUNGGU, atau RANDOM.";

                    continue;
                }

                $requestSearch =
                    $this->buildRequestSearch(
                        $platLengkap,
                        $nama,
                        $tanggalBulanTahun
                    );

                if (
                    $statusRequest === 'normal' &&
                    $requestSearch === null
                ) {
                    $statusRequest =
                        'menunggu';
                }

                if (
                    $statusRequest === 'random' &&
                    $requestSearch === null
                ) {
                    $platLengkap = 'RANDOM';
                    $requestSearch = 'RANDOM';
                }

                $groupedRows[$idItem][] = [
                    'baris' =>
                        $row,

                    'plat_lengkap' =>
                        $platLengkap,

                    'nama' =>
                        $nama,

                    'tanggal_bulan_tahun' =>
                        $tanggalBulanTahun,

                    'jumlah_editor' =>
                        (int) $jumlah,

                    'tanpa_heartbeat' =>
                        $this->excelBoolean(
                            $tanpaHeartbeat
                        ),

                    'status_request' =>
                        $statusRequest,

                    'request_search' =>
                        $requestSearch,
                ];
            }

            foreach (
                $groupedRows
                as $idPerProduk => $rows
            ) {
                if (
                    isset(
                        $invalidItemIds[
                            $idPerProduk
                        ]
                    )
                ) {
                    continue;
                }

                $statuses = collect($rows)
                    ->pluck('status_request')
                    ->unique()
                    ->values();

                if ($statuses->count() > 1) {
                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $errors[] =
                        "ID ITEM {$idPerProduk}: STATUS REQUEST harus sama pada semua baris.";
                }
            }

            $jumlahLocked = 0;
            $jumlahNormal = 0;
            $jumlahRandom = 0;
            $jumlahMenunggu = 0;
            $jumlahRequest = 0;

            foreach (
                $groupedRows
                as $idPerProduk => $rows
            ) {
                if (
                    isset(
                        $invalidItemIds[
                            $idPerProduk
                        ]
                    )
                ) {
                    continue;
                }

                try {
                    DB::transaction(
                        function () use (
                            $part,
                            $idPerProduk,
                            $rows,
                            &$jumlahLocked,
                            &$jumlahNormal,
                            &$jumlahRandom,
                            &$jumlahMenunggu,
                            &$jumlahRequest
                        ) {
                            $lockedPart =
                                EditorPart::where(
                                    'id',
                                    $part->id
                                )
                                    ->lockForUpdate()
                                    ->firstOrFail();

                            if (
                                $lockedPart->status ===
                                'processed'
                            ) {
                                return;
                            }

                            if (
                                $lockedPart->status !==
                                'downloaded'
                            ) {
                                throw new \Exception(
                                    "Part {$lockedPart->kode_part} sudah tidak dapat diproses."
                                );
                            }

                            $partItem =
                                EditorPartItem::where(
                                    'editor_part_id',
                                    $lockedPart->id
                                )
                                    ->where(
                                        'id_per_produk',
                                        $idPerProduk
                                    )
                                    ->lockForUpdate()
                                    ->first();

                            if (!$partItem) {
                                throw new \Exception(
                                    "ID ITEM {$idPerProduk} tidak ditemukan pada Part."
                                );
                            }

                            if (
                                $partItem->status !==
                                'pending'
                            ) {
                                return;
                            }

                            $status =
                                $rows[0][
                                    'status_request'
                                ];

                            if (
                                $status ===
                                'menunggu'
                            ) {
                                EditorRequest::where(
                                    'id_per_produk',
                                    $idPerProduk
                                )
                                    ->whereNull(
                                        'locked_at'
                                    )
                                    ->delete();

                                $partItem->update([
                                    'status' =>
                                        'skipped',

                                    'jumlah_final' =>
                                        null,

                                    'processed_at' =>
                                        now(),
                                ]);

                                $jumlahMenunggu++;

                                return;
                            }

                            $sudahLocked =
                                EditorRequest::where(
                                    'id_per_produk',
                                    $idPerProduk
                                )
                                    ->whereNotNull(
                                        'locked_at'
                                    )
                                    ->exists();

                            if ($sudahLocked) {
                                throw new \Exception(
                                    "ID ITEM {$idPerProduk} sudah dikunci."
                                );
                            }

                            EditorRequest::where(
                                'id_per_produk',
                                $idPerProduk
                            )
                                ->whereNull(
                                    'locked_at'
                                )
                                ->delete();

                            $jumlahFinal = 0;

                            foreach (
                                $rows
                                as $requestRow
                            ) {
                                EditorRequest::create([
                                    'id_per_produk' =>
                                        $idPerProduk,

                                    'editor_part_id' =>
                                        $lockedPart->id,

                                    'plat_lengkap' =>
                                        $requestRow[
                                            'plat_lengkap'
                                        ],

                                    'nama' =>
                                        $requestRow[
                                            'nama'
                                        ],

                                    'tanggal_bulan_tahun' =>
                                        $requestRow[
                                            'tanggal_bulan_tahun'
                                        ],

                                    'jumlah_editor' =>
                                        $requestRow[
                                            'jumlah_editor'
                                        ],

                                    'tanpa_heartbeat' =>
                                        $requestRow[
                                            'tanpa_heartbeat'
                                        ],

                                    'tanpa_korlantas' =>
                                        false,

                                    'status_request' =>
                                        $status,

                                    'request_search' =>
                                        $requestRow[
                                            'request_search'
                                        ],

                                    'editor_imported_by' =>
                                        Auth::id(),

                                    'editor_imported_at' =>
                                        now(),

                                    'locked_at' =>
                                        now(),

                                    'locked_by' =>
                                        Auth::id(),
                                ]);

                                $jumlahFinal +=
                                    (int) $requestRow[
                                        'jumlah_editor'
                                    ];

                                $jumlahRequest++;
                            }

                            $partItem->update([
                                'status' =>
                                    'locked',

                                'jumlah_final' =>
                                    $jumlahFinal,

                                'processed_at' =>
                                    now(),
                            ]);

                            $jumlahLocked++;

                            if (
                                $status ===
                                'random'
                            ) {
                                $jumlahRandom++;
                            } else {
                                $jumlahNormal++;
                            }
                        }
                    );
                } catch (\Throwable $e) {
                    report($e);

                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $errors[] =
                        "ID ITEM {$idPerProduk}: " .
                        $e->getMessage();
                }
            }

            $sudahDiproses =
                false;

            DB::transaction(
                function () use (
                    $part,
                    &$jumlahMenunggu,
                    &$sudahDiproses
                ) {
                    $lockedPart =
                        EditorPart::where(
                            'id',
                            $part->id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $lockedPart->status ===
                        'processed'
                    ) {
                        $sudahDiproses = true;

                        return;
                    }

                    if (
                        $lockedPart->status !==
                        'downloaded'
                    ) {
                        throw new \Exception(
                            "Part {$lockedPart->kode_part} sudah tidak dapat diselesaikan."
                        );
                    }

                    $sisaPending =
                        EditorPartItem::where(
                            'editor_part_id',
                            $lockedPart->id
                        )
                            ->where(
                                'status',
                                'pending'
                            )
                            ->lockForUpdate()
                            ->get();

                    foreach (
                        $sisaPending
                        as $pending
                    ) {
                        EditorRequest::where(
                            'id_per_produk',
                            $pending->id_per_produk
                        )
                            ->whereNull(
                                'locked_at'
                            )
                            ->delete();

                        $pending->update([
                            'status' =>
                                'skipped',

                            'jumlah_final' =>
                                null,

                            'processed_at' =>
                                now(),
                        ]);

                        $jumlahMenunggu++;
                    }

                    $lockedPart->update([
                        'status' =>
                            'processed',

                        'uploaded_by' =>
                            Auth::id(),

                        'uploaded_at' =>
                            now(),
                    ]);
                }
            );

            $spreadsheet
                ->disconnectWorksheets();

            $spreadsheet = null;

            if ($sudahDiproses) {
                return redirect()
                    ->route(
                        'editor.riwayat.index'
                    )
                    ->with(
                        'success',
                        "Part {$part->kode_part} sudah berhasil diproses."
                    );
            }

            $message =
                "Part {$part->kode_part} selesai. " .
                "{$jumlahLocked} item dikunci " .
                "({$jumlahNormal} normal, {$jumlahRandom} random), " .
                "{$jumlahRequest} request disimpan, " .
                "{$jumlahMenunggu} item masuk Menunggu Request.";

            $redirect = redirect()
                ->route(
                    'editor.riwayat.index'
                )
                ->with(
                    'success',
                    $message
                );

            if (!empty($errors)) {
                $redirect->with(
                    'import_errors',
                    $errors
                );
            }

            return $redirect;

        } catch (\Throwable $e) {
            report($e);

            if ($spreadsheet) {
                try {
                    $spreadsheet
                        ->disconnectWorksheets();
                } catch (\Throwable $ignored) {
                }
            }

            return back()->with(
                'error',
                'Gagal import Editor: ' .
                $e->getMessage()
            );
        }
    }

    public function menungguIndex()
    {
        $items = $this
            ->queryMenunggu()
            ->with([
                'part',
                'item.pesanan',
            ])
            ->get()
            ->sortBy(function ($partItem) {
                $pesanan = $partItem
                    ->item
                    ?->pesanan;

                try {
                    $deadline = $pesanan?->batas_kirim_at
                        ? Carbon::parse(
                            $pesanan->batas_kirim_at
                        )->format('Y-m-d H:i:s')
                        : '9999-12-31 23:59:59';
                } catch (\Throwable $e) {
                    $deadline = '9999-12-31 23:59:59';
                }

                try {
                    $tanggal = $pesanan?->tanggal
                        ? Carbon::parse(
                            $pesanan->tanggal
                        )->format('Y-m-d H:i:s')
                        : '9999-12-31 23:59:59';
                } catch (\Throwable $e) {
                    $tanggal = '9999-12-31 23:59:59';
                }

                return sprintf(
                    '%s|%s|%020d',
                    $deadline,
                    $tanggal,
                    (int) $partItem->id
                );
            })
            ->values();

        return view(
            'editor.menunggu.index',
            compact('items')
        );
    }

    public function menungguSiap(
        EditorPartItem $partItem,
        EditorPartService $partService
    ) {
        if (
            $partItem->status !==
            'skipped'
        ) {
            return back()->with(
                'error',
                'Item ini sudah tidak berstatus MENUNGGU.'
            );
        }

        $adaPartSetelahnya =
            EditorPartItem::where(
                'id_per_produk',
                $partItem
                    ->id_per_produk
            )
                ->where(
                    'id',
                    '>',
                    $partItem->id
                )
                ->exists();

        if ($adaPartSetelahnya) {
            return back()->with(
                'error',
                'Item ini sudah masuk ke Part berikutnya.'
            );
        }

        try {
            $hasil =
                DB::transaction(
                    function () use (
                        $partItem,
                        $partService
                    ) {
                        return $partService
                            ->alokasikanItemBaru(
                                [
                                    $partItem
                                        ->id_per_produk,
                                ],
                                Auth::id()
                            );
                    }
                );

            if (
                ($hasil['items'] ?? 0)
                < 1
            ) {
                $oversize =
                    $hasil[
                        'oversize'
                    ]
                    ?? [];

                if (
                    !empty($oversize)
                ) {
                    return back()->with(
                        'error',
                        'Jumlah item melebihi kapasitas 52 dan harus ditangani manual.'
                    );
                }

                return back()->with(
                    'error',
                    'Item gagal dimasukkan ke Part Produksi.'
                );
            }

            return redirect()
                ->route(
                    'editor.part.index'
                )
                ->with(
                    'success',
                    'Request customer sudah tersedia. Item berhasil dimasukkan ke Part Produksi.'
                );
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Gagal memasukkan item ke Part: ' .
                $e->getMessage()
            );
        }
    }

    public function riwayatIndex()
    {
        $parts =
            EditorPart::withCount([
                'items as jumlah_item',

                'items as locked_count' =>
                    fn ($q) =>
                    $q->where(
                        'status',
                        'locked'
                    ),

                'items as skipped_count' =>
                    fn ($q) =>
                    $q->where(
                        'status',
                        'skipped'
                    ),
            ])
                ->where(
                    'status',
                    'processed'
                )
                ->orderByDesc(
                    'tanggal_part'
                )
                ->orderByDesc(
                    'nomor_part'
                )
                ->paginate(30);

        return view(
            'editor.riwayat.index',
            compact('parts')
        );
    }

    private function queryMenunggu()
    {
        return EditorPartItem::query()
            ->where('editor_part_items.status', 'skipped')
            ->whereHas('item.pesanan', function ($q) {
                $q->where('status', 'proses');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('editor_part_items as newer')
                    ->whereColumn(
                        'newer.id_per_produk',
                        'editor_part_items.id_per_produk'
                    )
                    ->whereColumn(
                        'newer.id',
                        '>',
                        'editor_part_items.id'
                    );
            });
    }

    private function validasiHeader(
        $sheet
    ): void {
        $headers = [
            'A1' => 'SKU',
            'B1' => 'PLAT LENGKAP',
            'C1' => 'NAMA',
            'D1' => 'TANGGAL/BULAN TAHUN',
            'E1' => 'JUMLAH',
            'F1' => 'TANPA HEARTBEAT',
            'G1' => 'ID ITEM',
            'H1' => 'NO PESANAN',
            'I1' => 'STATUS REQUEST',
            'J1' => 'BATAS KIRIM',
        ];

        foreach (
            $headers
            as $cell => $expected
        ) {
            $actual =
                strtoupper(
                    trim(
                        (string)
                        $sheet
                            ->getCell(
                                $cell
                            )
                            ->getFormattedValue()
                    )
                );

            if (
                $actual !==
                $expected
            ) {
                throw new \Exception(
                    "Format Excel tidak valid. {$cell} harus berisi {$expected}."
                );
            }
        }
    }

    private function buatMetaSheet(
        Spreadsheet $spreadsheet,
        EditorPart $part
    ): void {
        $meta =
            $spreadsheet
                ->getSheetByName(
                    'META'
                );

        if (!$meta) {
            $meta =
                new Worksheet(
                    $spreadsheet,
                    'META'
                );

            $spreadsheet
                ->addSheet(
                    $meta
                );
        }

        $meta->setCellValue(
            'A1',
            'KODE PART'
        );

        $meta->setCellValueExplicit(
            'B1',
            (string)
            $part->kode_part,
            DataType::TYPE_STRING
        );

        $meta->setCellValue(
            'A2',
            'PART ID'
        );

        $meta->setCellValue(
            'B2',
            (int) $part->id
        );

        $meta->setCellValue(
            'A3',
            'TANGGAL PART'
        );

        $meta->setCellValue(
            'B3',
            $this->formatTanggalPart(
                $part->tanggal_part
            )
        );

        $meta->setSheetState(
            Worksheet::SHEETSTATE_HIDDEN
        );
    }

    private function ambilPartDariExcel(
        Spreadsheet $spreadsheet
    ): ?EditorPart {
        $meta =
            $spreadsheet
                ->getSheetByName(
                    'META'
                );

        if (!$meta) {
            return null;
        }

        $kodePart =
            trim(
                (string)
                $meta
                    ->getCell(
                        'B1'
                    )
                    ->getFormattedValue()
            );

        $partId =
            trim(
                (string)
                $meta
                    ->getCell(
                        'B2'
                    )
                    ->getFormattedValue()
            );

        if (
            $kodePart === '' ||
            $partId === '' ||
            !ctype_digit(
                (string)
                $partId
            )
        ) {
            return null;
        }

        return EditorPart::where(
            'id',
            (int) $partId
        )
            ->where(
                'kode_part',
                $kodePart
            )
            ->first();
    }

    private function normalizeStatusRequest(
        $value
    ): ?string {
        $value =
            mb_strtoupper(
                trim(
                    (string)
                    $value
                )
            );

        if (
            $value === '' ||
            $value === 'NORMAL'
        ) {
            return 'normal';
        }

        if (
            $value ===
            'MENUNGGU'
        ) {
            return 'menunggu';
        }

        if (
            $value ===
            'RANDOM'
        ) {
            return 'random';
        }

        return null;
    }

    private function nullableText(
        $value
    ): ?string {
        $value =
            (string) $value;

        $value =
            preg_replace(
                '/[\x{00A0}\x{200B}\x{FEFF}]/u',
                ' ',
                $value
            );

        $value =
            trim(
                $value
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function buildRequestSearch(
        ?string $platLengkap,
        ?string $nama,
        ?string $tanggalBulanTahun
    ): ?string {
        $parts = [];

        if (
            $platLengkap !== null &&
            trim(
                $platLengkap
            ) !== ''
        ) {
            $parts[] =
                trim(
                    $platLengkap
                );
        }

        if (
            $nama !== null &&
            trim(
                $nama
            ) !== ''
        ) {
            $parts[] =
                trim(
                    $nama
                );
        }

        if (
            $tanggalBulanTahun !== null &&
            trim(
                $tanggalBulanTahun
            ) !== ''
        ) {
            $parts[] =
                trim(
                    $tanggalBulanTahun
                );
        }

        if (empty($parts)) {
            return null;
        }

        return $this
            ->normalizeRequestSearch(
                implode(
                    ' ',
                    $parts
                )
            );
    }

    private function normalizeRequestSearch(
        ?string $value
    ): ?string {
        $value =
            mb_strtoupper(
                trim(
                    (string)
                    $value
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

    private function excelBoolean(
        $value
    ): bool {
        $value =
            mb_strtoupper(
                trim(
                    (string)
                    $value
                )
            );

        return in_array(
            $value,
            [
                '1',
                'YA',
                'YES',
                'TRUE',
                'Y',
                'X',
            ],
            true
        );
    }

    private function formatBatasKirim(
        $value
    ): string {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse(
                $value
            )->format(
                'd/m/Y H:i'
            );
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function formatTanggalPart(
        $value
    ): string {
        if (!$value) {
            return now()
                ->toDateString();
        }

        try {
            return Carbon::parse(
                $value
            )->format(
                'Y-m-d'
            );
        } catch (\Throwable $e) {
            return now()
                ->toDateString();
        }
    }

    public function downloadQrPart(EditorPart $part)
    {
        if ($part->status !== 'processed') {
            return back()->with(
                'error',
                'QR Code hanya dapat dibuat setelah Part selesai diproses.'
            );
        }

        $requests = DB::table('editor_requests as er')
            ->join(
                'pesanan_per_produk as pp',
                'pp.id_per_produk',
                '=',
                'er.id_per_produk'
            )
            ->leftJoin(
                'produk as pr',
                'pr.sku',
                '=',
                'pp.sku'
            )
            ->join(
                'editor_part_items as epi',
                function ($join) use ($part) {
                    $join->on(
                        'epi.id_per_produk',
                        '=',
                        'er.id_per_produk'
                    )
                        ->where(
                            'epi.editor_part_id',
                            '=',
                            $part->id
                        );
                }
            )
            ->where(
                'er.editor_part_id',
                $part->id
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
                'er.plat_lengkap',
                'er.nama',
                'er.tanggal_bulan_tahun',
                'er.jumlah_editor',
                'er.status_request',

                'pp.no_pesanan',
                'pp.sku',
                'pp.nama_produk as pp_nama_produk',
                'pp.variasi as pp_variasi',

                'pr.nama_produk as master_nama_produk',
                'pr.variasi as master_variasi',

                'epi.urutan',
            ])
            ->orderByRaw("
                CASE
                    WHEN COALESCE(
                        NULLIF(TRIM(pr.variasi), ''),
                        NULLIF(TRIM(pp.variasi), '')
                    ) IS NULL
                    THEN 1
                    ELSE 0
                END
            ")
            ->orderByRaw("
                LOWER(
                    COALESCE(
                        NULLIF(TRIM(pr.variasi), ''),
                        NULLIF(TRIM(pp.variasi), ''),
                        ''
                    )
                ) ASC
            ")
            ->orderByRaw("
                LOWER(TRIM(pp.sku)) ASC
            ")
            ->orderBy(
                'epi.urutan',
                'asc'
            )
            ->orderBy(
                'er.id',
                'asc'
            )
            ->get();

        if ($requests->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada hasil Editor yang dapat dibuat QR Code.'
            );
        }

        $renderer = new ImageRenderer(
            new RendererStyle(
                180,
                1
            ),
            new SvgImageBackEnd()
        );

        $writer = new Writer(
            $renderer
        );

        $rows = collect();

        foreach ($requests as $request) {
            $jumlah = max(
                1,
                (int) $request->jumlah_editor
            );

            $namaProduk = trim(
                (string) (
                    $request->master_nama_produk
                    ?: $request->pp_nama_produk
                    ?: ''
                )
            );

            $variasi = trim(
                (string) (
                    $request->master_variasi
                    ?: $request->pp_variasi
                    ?: ''
                )
            );

            $sku = strtoupper(
                trim(
                    (string) $request->sku
                )
            );

            $svg = $writer->writeString(
                (string) $request->no_pesanan
            );

            $qrCode =
                'data:image/svg+xml;base64,' .
                base64_encode($svg);

            for ($i = 1; $i <= $jumlah; $i++) {
                $rows->push([
                    'id_per_produk' =>
                        $request->id_per_produk,

                    'no_pesanan' =>
                        $request->no_pesanan,

                    'sku' =>
                        $sku,

                    'nama_produk' =>
                        $namaProduk,

                    'variasi' =>
                        $variasi,

                    'plat_lengkap' =>
                        $request->plat_lengkap,

                    'nama' =>
                        $request->nama,

                    'tanggal_bulan_tahun' =>
                        $request->tanggal_bulan_tahun,

                    'status_request' =>
                        $request->status_request,

                    'unit' =>
                        $i,

                    'jumlah' =>
                        $jumlah,

                    'qr_code' =>
                        $qrCode,
                ]);
            }
        }

        $pages = collect();

        $rows
            ->groupBy('sku')
            ->each(
                function ($skuRows) use (&$pages) {
                    $skuRows
                        ->chunk(7)
                        ->each(
                            function ($pageRows) use (&$pages) {
                                $pages->push(
                                    $pageRows->values()
                                );
                            }
                        );
                }
            );

        $pdf = Pdf::loadView(
            'editor.part.qr-pdf',
            compact(
                'part',
                'pages'
            )
        );

        $pdf->setPaper(
            [
                0,
                0,
                283.46,
                425.20,
            ],
            'portrait'
        );

        return $pdf->download(
            'QR_' .
            $part->kode_part .
            '.pdf'
        );
    }
}

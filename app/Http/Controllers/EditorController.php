<?php

namespace App\Http\Controllers;

use App\Models\EditorRequest;
use App\Models\PesananPerProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EditorController extends Controller
{
    public function index()
    {
        $totalBelumEditor = PesananPerProduk::query()
            ->whereNotNull('sku')
            ->where('sku', 'like', 'PLT%')
            ->whereHas('pesanan', function ($q) {
                $q->where('status', 'proses');
            })
            ->whereDoesntHave('editorRequests')
            ->count();

        $totalSelesaiEditor = PesananPerProduk::query()
            ->whereNotNull('sku')
            ->where('sku', 'like', 'PLT%')
            ->whereHas('pesanan', function ($q) {
                $q->where('status', 'proses');
            })
            ->whereHas('editorRequests')
            ->count();

        return view('editor.index', [
            'totalBelumEditor' => $totalBelumEditor,
            'totalSelesaiEditor' => $totalSelesaiEditor,
        ]);
    }

    public function downloadPlat(Request $request)
    {
        $templatePath = storage_path(
            'app/templates/editor_plat.xlsx'
        );

        if (!file_exists($templatePath)) {
            return back()->with(
                'error',
                'Template editor_plat.xlsx tidak ditemukan.'
            );
        }

        $items = PesananPerProduk::query()
            ->with('pesanan')
            ->whereNotNull('sku')
            ->where('sku', 'like', 'PLT%')
            ->whereHas('pesanan', function ($q) {
                $q->where('status', 'proses');
            })
            ->whereDoesntHave('editorRequests')
            ->orderBy('id_per_produk')
            ->get();

        if ($items->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada pekerjaan Editor yang tersedia.'
            );
        }

        $spreadsheet = IOFactory::load(
            $templatePath
        );

        $sheet = $spreadsheet->getSheetByName(
            'PLAT'
        );

        if (!$sheet) {
            $spreadsheet->disconnectWorksheets();

            return back()->with(
                'error',
                'Sheet PLAT tidak ditemukan pada template.'
            );
        }

        $sheet->setCellValue('A1', 'SKU');
        $sheet->setCellValue('B1', 'PLAT LENGKAP');
        $sheet->setCellValue('C1', 'NAMA');
        $sheet->setCellValue('D1', 'TANGGAL/BULAN TAHUN');
        $sheet->setCellValue('E1', 'JUMLAH');
        $sheet->setCellValue('F1', 'TANPA HEARTBEAT');
        $sheet->setCellValue('G1', 'TANPA KORLANTAS');
        $sheet->setCellValue('H1', 'ID ITEM');
        $sheet->setCellValue('I1', 'NO PESANAN');

        $highestRow = max(
            2,
            $sheet->getHighestDataRow()
        );

        for ($row = 2; $row <= $highestRow; $row++) {
            for ($column = 'A'; $column <= 'I'; $column++) {
                $sheet
                    ->getCell($column . $row)
                    ->setValue(null);
            }
        }

        $row = 2;

        foreach ($items as $item) {
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

            $sheet->setCellValue(
                'G' . $row,
                ''
            );

            $sheet->setCellValueExplicit(
                'H' . $row,
                (string) $item->id_per_produk,
                DataType::TYPE_STRING
            );

            $sheet->setCellValueExplicit(
                'I' . $row,
                (string) $item->no_pesanan,
                DataType::TYPE_STRING
            );

            $row++;
        }

        $lastRow = $row - 1;

        $sheet
            ->getStyle('A2:A' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('@');

        $sheet
            ->getStyle('H2:I' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('@');

        $filename =
            'EDITOR_PLAT_' .
            now()->format('Y-m-d_H-i-s') .
            '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx(
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

        try {
            $spreadsheet = IOFactory::load(
                $request
                    ->file('file_editor')
                    ->getRealPath()
            );

            $sheet = $spreadsheet->getSheetByName(
                'PLAT'
            );

            if (!$sheet) {
                $spreadsheet->disconnectWorksheets();

                return back()->with(
                    'error',
                    'Sheet PLAT tidak ditemukan.'
                );
            }

            $headerIdItem = strtoupper(
                trim(
                    (string) $sheet
                        ->getCell('H1')
                        ->getFormattedValue()
                )
            );

            $headerNoPesanan = strtoupper(
                trim(
                    (string) $sheet
                        ->getCell('I1')
                        ->getFormattedValue()
                )
            );

            if (
                $headerIdItem !== 'ID ITEM' ||
                $headerNoPesanan !== 'NO PESANAN'
            ) {
                $spreadsheet->disconnectWorksheets();

                return back()->with(
                    'error',
                    'Format Excel tidak valid. Kolom ID ITEM dan NO PESANAN tidak ditemukan.'
                );
            }

            $highestRow = $sheet->getHighestDataRow();

            $groupedRequests = [];
            $invalidItemIds = [];
            $errors = [];
            $dilewati = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
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

                $tanpaKorlantas = $this->nullableText(
                    $sheet
                        ->getCell("G{$row}")
                        ->getFormattedValue()
                );

                $idItem = $this->nullableText(
                    $sheet
                        ->getCell("H{$row}")
                        ->getFormattedValue()
                );

                $noPesanan = $this->nullableText(
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
                    $idItem === null &&
                    $noPesanan === null
                ) {
                    continue;
                }

                if ($idItem === null) {
                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: ID ITEM kosong.";

                    continue;
                }

                $item = PesananPerProduk::with(
                    'pesanan'
                )
                    ->where(
                        'id_per_produk',
                        $idItem
                    )
                    ->first();

                if (!$item) {
                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: ID ITEM {$idItem} tidak ditemukan.";

                    continue;
                }

                $idPerProduk =
                    (string) $item->id_per_produk;

                if ($noPesanan === null) {
                    $noPesanan =
                        (string) $item->no_pesanan;
                }

                if (
                    (string) $item->no_pesanan !==
                    (string) $noPesanan
                ) {
                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: NO PESANAN {$noPesanan} tidak cocok dengan ID ITEM {$idItem}.";

                    continue;
                }

                if (
                    !$item->pesanan ||
                    $item->pesanan->status !== 'proses'
                ) {
                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: Pesanan {$noPesanan} sudah tidak berstatus proses.";

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
                        trim($sku)
                    )
                ) {
                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: SKU {$sku} tidak cocok untuk ID ITEM {$idItem}.";

                    continue;
                }

                if (
                    $jumlah === null ||
                    !is_numeric($jumlah) ||
                    (int) $jumlah < 1
                ) {
                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: JUMLAH harus minimal 1.";

                    continue;
                }

                $requestSearch =
                    $this->buildRequestSearch(
                        $platLengkap,
                        $nama,
                        $tanggalBulanTahun
                    );

                if ($requestSearch === null) {
                    $invalidItemIds[
                        $idPerProduk
                    ] = true;

                    $dilewati++;

                    $errors[] =
                        "Baris {$row}: request customer kosong.";

                    continue;
                }

                $groupedRequests[
                    $idPerProduk
                ][] = [
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

                    'tanpa_korlantas' =>
                        $this->excelBoolean(
                            $tanpaKorlantas
                        ),

                    'request_search' =>
                        $requestSearch,
                ];
            }

            $validRequests = [];

            foreach (
                $groupedRequests
                as $idPerProduk => $requestRows
            ) {
                if (
                    isset(
                        $invalidItemIds[
                            $idPerProduk
                        ]
                    )
                ) {
                    $errors[] =
                        "ID ITEM {$idPerProduk} tidak disimpan karena memiliki baris yang tidak valid.";

                    continue;
                }

                $validRequests[
                    $idPerProduk
                ] = $requestRows;
            }

            if (empty($validRequests)) {
                $spreadsheet->disconnectWorksheets();

                return back()
                    ->with(
                        'error',
                        'Tidak ada data Editor yang valid untuk disimpan.'
                    )
                    ->with(
                        'import_errors',
                        $errors
                    );
            }

            $jumlahItem = 0;
            $jumlahRequest = 0;

            DB::transaction(
                function () use (
                    $validRequests,
                    &$jumlahItem,
                    &$jumlahRequest
                ) {
                    foreach (
                        $validRequests
                        as $idPerProduk => $requestRows
                    ) {
                        EditorRequest::where(
                            'id_per_produk',
                            $idPerProduk
                        )->delete();

                        foreach (
                            $requestRows
                            as $requestRow
                        ) {
                            EditorRequest::create([
                                'id_per_produk' =>
                                    $idPerProduk,

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
                                    $requestRow[
                                        'tanpa_korlantas'
                                    ],

                                'request_search' =>
                                    $requestRow[
                                        'request_search'
                                    ],

                                'editor_imported_by' =>
                                    Auth::id(),

                                'editor_imported_at' =>
                                    now(),
                            ]);

                            $jumlahRequest++;
                        }

                        $jumlahItem++;
                    }
                }
            );

            $spreadsheet->disconnectWorksheets();

            return back()
                ->with(
                    'success',
                    "Import berhasil. {$jumlahItem} item dengan {$jumlahRequest} request disimpan, {$dilewati} baris dilewati."
                )
                ->with(
                    'import_errors',
                    $errors
                );

        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Gagal membaca file Excel: ' .
                $e->getMessage()
            );
        }
    }

    private function nullableText(
        $value
    ): ?string {
        $value = (string) $value;

        $value = preg_replace(
            '/[\x{00A0}\x{200B}\x{FEFF}]/u',
            ' ',
            $value
        );

        $value = trim(
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
            trim($platLengkap) !== ''
        ) {
            $parts[] =
                trim($platLengkap);
        }

        if (
            $nama !== null &&
            trim($nama) !== ''
        ) {
            $parts[] =
                trim($nama);
        }

        if (
            $tanggalBulanTahun !== null &&
            trim($tanggalBulanTahun) !== ''
        ) {
            $parts[] =
                trim(
                    $tanggalBulanTahun
                );
        }

        if (empty($parts)) {
            return null;
        }

        return $this->normalizeRequestSearch(
            implode(
                ' ',
                $parts
            )
        );
    }

    private function normalizeRequestSearch(
        ?string $value
    ): ?string {
        $value = mb_strtoupper(
            trim(
                (string) $value
            )
        );

        $value = preg_replace(
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
        $value = mb_strtoupper(
            trim(
                (string) $value
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
}

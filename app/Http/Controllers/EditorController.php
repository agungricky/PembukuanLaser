<?php

namespace App\Http\Controllers;

use App\Models\PesananPerProduk;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\EditorRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ->whereDoesntHave('editorRequest')
            ->count();

        $totalSelesaiEditor = PesananPerProduk::query()
            ->whereNotNull('sku')
            ->where('sku', 'like', 'PLT%')
            ->whereHas('pesanan', function ($q) {
                $q->where('status', 'proses');
            })
            ->whereHas('editorRequest')
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

            ->with([
                'pesanan',
                'editorRequest',
            ])

            ->whereNotNull('sku')

            ->where('sku', 'like', 'PLT%')

            ->whereHas('pesanan', function ($q) {
                $q->where('status', 'proses');
            })

            ->whereDoesntHave('editorRequest')

            ->orderBy('id_per_produk')
            ->get();


        if ($items->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada pekerjaan Editor yang tersedia.'
            );
        }

        $spreadsheet = IOFactory::load($templatePath);

        $sheet = $spreadsheet->getSheetByName('PLAT');

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

            $sheet->setCellValue('B' . $row, '');
            $sheet->setCellValue('C' . $row, '');
            $sheet->setCellValue('D' . $row, '');

            $sheet->setCellValue(
                'E' . $row,
                (int) $item->jumlah
            );

            $sheet->setCellValue('F' . $row, '');
            $sheet->setCellValue('G' . $row, '');

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

                $writer = new Xlsx($spreadsheet);

                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();

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
            'file_editor' => 'required|file|mimes:xlsx,xls,xlsm|max:20480',
        ]);

        try {
            $spreadsheet = IOFactory::load(
                $request->file('file_editor')->getRealPath()
            );

            $sheet = $spreadsheet->getSheetByName('PLAT');

            if (!$sheet) {
                return back()->with(
                    'error',
                    'Sheet PLAT tidak ditemukan.'
                );
            }

            $headerIdItem = strtoupper(
                trim((string) $sheet->getCell('H1')->getFormattedValue())
            );

            $headerNoPesanan = strtoupper(
                trim((string) $sheet->getCell('I1')->getFormattedValue())
            );

            if (
                $headerIdItem !== 'ID ITEM' ||
                $headerNoPesanan !== 'NO PESANAN'
            ) {
                return back()->with(
                    'error',
                    'Format Excel tidak valid. Kolom ID ITEM dan NO PESANAN tidak ditemukan.'
                );
            }

            $highestRow = $sheet->getHighestDataRow();

            $berhasil = 0;
            $dilewati = 0;
            $errors = [];

            DB::transaction(function () use (
                $sheet,
                $highestRow,
                &$berhasil,
                &$dilewati,
                &$errors
            ) {
                for ($row = 2; $row <= $highestRow; $row++) {

                    $sku = trim(
                        (string) $sheet->getCell("A{$row}")->getFormattedValue()
                    );

                    $platLengkap = trim(
                        (string) $sheet->getCell("B{$row}")->getFormattedValue()
                    );

                    $nama = trim(
                        (string) $sheet->getCell("C{$row}")->getFormattedValue()
                    );

                    $tanggalBulanTahun = trim(
                        (string) $sheet->getCell("D{$row}")->getFormattedValue()
                    );

                    $jumlah = trim(
                        (string) $sheet->getCell("E{$row}")->getFormattedValue()
                    );

                    $tanpaHeartbeat = trim(
                        (string) $sheet->getCell("F{$row}")->getFormattedValue()
                    );

                    $tanpaKorlantas = trim(
                        (string) $sheet->getCell("G{$row}")->getFormattedValue()
                    );

                    $idItem = trim(
                        (string) $sheet->getCell("H{$row}")->getFormattedValue()
                    );

                    $noPesanan = trim(
                        (string) $sheet->getCell("I{$row}")->getFormattedValue()
                    );

                    if ($idItem === '' && $noPesanan === '') {
                        continue;
                    }

                    if ($idItem === '') {
                        $dilewati++;

                        $errors[] =
                            "Baris {$row}: ID ITEM kosong.";

                        continue;
                    }

                    $item = PesananPerProduk::with('pesanan')
                        ->where('id_per_produk', $idItem)
                        ->first();

                    if (!$item) {
                        $dilewati++;

                        $errors[] =
                            "Baris {$row}: ID ITEM {$idItem} tidak ditemukan.";

                        continue;
                    }

                    if ((string) $item->no_pesanan !== (string) $noPesanan) {
                        $dilewati++;

                        $errors[] =
                            "Baris {$row}: NO PESANAN tidak cocok dengan ID ITEM {$idItem}.";

                        continue;
                    }

                    if (
                        $item->pesanan &&
                        $item->pesanan->status !== 'proses'
                    ) {
                        $dilewati++;

                        $errors[] =
                            "Baris {$row}: Pesanan {$noPesanan} sudah tidak berstatus proses.";

                        continue;
                    }

                    if (
                        $sku !== '' &&
                        strtoupper((string) $item->sku) !== strtoupper($sku)
                    ) {
                        $dilewati++;

                        $errors[] =
                            "Baris {$row}: SKU tidak cocok untuk ID ITEM {$idItem}.";

                        continue;
                    }

                    EditorRequest::updateOrCreate(
                        [
                            'id_per_produk' => $item->id_per_produk,
                        ],
                        [
                            'plat_lengkap' => $platLengkap ?: null,
                            'nama' => $nama ?: null,
                            'tanggal_bulan_tahun' => $tanggalBulanTahun ?: null,
                            'jumlah_editor' => is_numeric($jumlah)
                                ? (int) $jumlah
                                : null,
                            'tanpa_heartbeat' => $this->excelBoolean(
                                $tanpaHeartbeat
                            ),
                            'tanpa_korlantas' => $this->excelBoolean(
                                $tanpaKorlantas
                            ),
                            'request_search' => $this->normalizeRequest(
                                $platLengkap
                            ),
                            'editor_imported_by' => Auth::id(),
                            'editor_imported_at' => now(),
                        ]
                    );

                    $berhasil++;
                }
            });

            $spreadsheet->disconnectWorksheets();

            return back()
                ->with(
                    'success',
                    "Import berhasil. {$berhasil} item disimpan, {$dilewati} item dilewati."
                )
                ->with(
                    'import_errors',
                    $errors
                );

        } catch (\Throwable $e) {

            report($e);

            return back()->with(
                'error',
                'Gagal membaca file Excel: ' . $e->getMessage()
            );
        }
    }

    private function excelBoolean($value): bool
    {
        $value = strtoupper(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                '1',
                'YA',
                'YES',
                'TRUE',
                'Y',
                'X'
            ],
            true
        );
    }

    private function normalizeRequest($value): ?string
    {
        $value = strtoupper(
            trim((string) $value)
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        return $value !== ''
            ? $value
            : null;
    }
}

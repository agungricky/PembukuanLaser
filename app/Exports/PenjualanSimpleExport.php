<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PenjualanSimpleExport implements FromArray, WithStyles, WithEvents, ShouldAutoSize
{
    public function __construct(
        private array $meta,
        private array $dailyRows
    ) {}

    public function array(): array
    {
        $rows = [];

        // Judul
        $rows[] = ['LAPORAN PENJUALAN SHOPEE'];

        // Info periode & toko
        $rows[] = ['Periode', $this->meta['period'] ?? '', 'Toko', $this->meta['store'] ?? ''];
        $rows[] = ['Generated At', $this->meta['generated'] ?? '', '', ''];

        // Baris kosong
        $rows[] = [];

        // ===== Ringkasan HANYA 8 KOLOM =====
        $rows[] = [
            'OMSET', 'TOTAL HPP', 'BIAYA ADMIN', 'BIAYA IKLAN',
            'SELISIH', 'KEUNTUNGAN BERSIH', 'TOTAL PESANAN', 'PESANAN BATAL',
        ];

        // Ambil nilai dari meta (controller sudah isi semuanya)
        $omset        = (float)($this->meta['omset']         ?? array_sum(array_column($this->dailyRows, 1)));
        $totalHpp     = (float)($this->meta['total_hpp']     ?? 0.0);
        $totalAdmin   = (float)($this->meta['total_admin']   ?? 0.0);
        $biayaIklan   = (float)($this->meta['ad_spend']      ?? 0.0);
        $selisih      = (float)($this->meta['selisih']       ?? 0.0);
        $netProfit    = (float)($this->meta['net_profit']    ?? 0.0);
        $totalPesanan = (int)  ($this->meta['total_pesanan'] ?? 0);
        $pesananBatal = (int)  ($this->meta['pesanan_batal'] ?? 0);

        $rows[] = [
            $omset,
            $totalHpp,
            $totalAdmin,
            $biayaIklan,
            $selisih,
            $netProfit,
            $totalPesanan,
            $pesananBatal,
        ];

        // Baris kosong
        $rows[] = [];

        // ===== Tabel Harian: Tanggal, Penjualan, HPP, Pesanan, Pesanan Dibatalkan =====
        $rows[] = ['Tanggal', 'Penjualan', 'HPP', 'Pesanan', 'Pesanan Dibatalkan'];
        foreach ($this->dailyRows as $r) {
            // [$date, $sales, $hpp, $orders, $canceled]
            $rows[] = [$r[0], (float)$r[1], (float)$r[2], (int)$r[3], (int)$r[4]];
        }

        // TOTAL harian
        $rows[] = [];
        $rows[] = [
            'TOTAL',
            array_sum(array_column($this->dailyRows, 1)), // Penjualan
            array_sum(array_column($this->dailyRows, 2)), // HPP
            array_sum(array_column($this->dailyRows, 3)), // Pesanan
            array_sum(array_column($this->dailyRows, 4)), // Pesanan Dibatalkan
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Spasi rapi
                $sheet->insertNewRowBefore(4, 1); // kosong antar info & ringkasan
                $sheet->insertNewRowBefore(7, 1); // kosong sebelum tabel harian

                // Merge judul (8 kolom: A..H)
                $sheet->mergeCells('A1:H1');
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:H1')->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER);

                // Info box (A2..H3)
                $sheet->mergeCells('B2:E2'); // Periode value lebar
                $sheet->mergeCells('G2:H2'); // Toko value lebar
                $sheet->mergeCells('B3:H3'); // Generated at value

                $sheet->getStyle('A2:H3')->getFill()
                      ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5FF');
                $sheet->getStyle('A2:H3')->getBorders()->getOutline()
                      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('D2')->getFont()->setBold(true);
                $sheet->getStyle('A3')->getFont()->setBold(true);

                $sheet->getStyle('A2:H3')->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                      ->setVertical(Alignment::VERTICAL_CENTER);

                // Header ringkasan (row 5)
                $sheet->getStyle('A5:H5')->getFont()->setBold(true);
                $sheet->getStyle('A5:H5')->getFill()
                      ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');

                // Format angka ringkasan (row 6)
                // Currency: A..F, Integer: G..H
                $sheet->getStyle('A6:F6')->getNumberFormat()
                      ->setFormatCode('"Rp"* #,##0;[Red]"Rp"* -#,##0');
                $sheet->getStyle('G6:H6')->getNumberFormat()
                      ->setFormatCode('#,##0');

                // Header tabel harian (A8:E8)
                $sheet->getStyle('A8:E8')->getFont()->setBold(true);
                $sheet->getStyle('A8:E8')->getFill()
                      ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');

                // Cari baris TOTAL
                $lastRow = $sheet->getHighestRow();
                $totalRow = $lastRow;
                for ($i = $lastRow; $i >= 1; $i--) {
                    $val = (string)$sheet->getCell("A{$i}")->getValue();
                    if (trim($val) === 'TOTAL') { $totalRow = $i; break; }
                }

                $dataStart = 9;
                $dataEnd   = $totalRow - 2;

                if ($dataEnd >= $dataStart) {
                    // Format kolom harian
                    $sheet->getStyle("A{$dataStart}:A{$dataEnd}")
                          ->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                    $sheet->getStyle("B{$dataStart}:B{$dataEnd}") // Penjualan
                          ->getNumberFormat()->setFormatCode('"Rp"* #,##0;[Red]"Rp"* -#,##0');
                    $sheet->getStyle("C{$dataStart}:C{$dataEnd}") // HPP
                          ->getNumberFormat()->setFormatCode('"Rp"* #,##0;[Red]"Rp"* -#,##0');
                    $sheet->getStyle("D{$dataStart}:D{$dataEnd}") // Pesanan
                          ->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("E{$dataStart}:E{$dataEnd}") // Pesanan Dibatalkan
                          ->getNumberFormat()->setFormatCode('#,##0');

                    // Freeze header harian & autofilter
                    $sheet->freezePane("A{$dataStart}");
                    $sheet->setAutoFilter("A8:E{$dataEnd}");

                    // Border tipis
                    $sheet->getStyle("A{$dataStart}:E{$dataEnd}")
                          ->getBorders()->getAllBorders()
                          ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
                }

                // Format baris TOTAL harian
                $sheet->getStyle("A{$totalRow}:E{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("B{$totalRow}")->getNumberFormat()->setFormatCode('"Rp"* #,##0;[Red]"Rp"* -#,##0');
                $sheet->getStyle("C{$totalRow}")->getNumberFormat()->setFormatCode('"Rp"* #,##0;[Red]"Rp"* -#,##0');
                $sheet->getStyle("D{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("E{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
            }
        ];
    }
}

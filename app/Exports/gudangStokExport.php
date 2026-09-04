<?php

namespace App\Exports;

use App\Models\Produk;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class gudangStokExport implements FromArray, WithEvents, WithTitle, WithStrictNullComparison
{
    protected $data;
    protected $dataCount;

    public function __construct()
    {
        $this->data = Produk::with('stok_produk')->get();
    }

    public function array(): array
    {
        $rows = [
            ['', '', '', ''],
            ['SKU', 'NAMA PRODUK', 'VARIASI', 'JUMLAH STOK'],
        ];

        foreach ($this->data as $item) {
            $rows[] = [
                $item->sku,
                $item->nama_produk,
                $item->variasi,
                $item->stok_produk?->jumlah_tersedia ?? 0,
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'DAFTAR STOK');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(40);

                $sheet->setCellValue('A2', 'SKU');
                $sheet->setCellValue('B2', 'NAMA PRODUK');
                $sheet->setCellValue('C2', 'VARIASI');
                $sheet->setCellValue('D2', 'JUMLAH STOK');

                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0D47A1'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                ]);

                $this->dataCount = count($this->data);
                $awal = 3;
                $akhir = $awal + $this->dataCount - 1;

                $sheet->getStyle("A{$awal}:B{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'start',
                        'vertical' => 'center',
                    ],
                ]);

                $sheet->getStyle("C{$awal}:D{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                $sheet->getStyle("A{$awal}:D{$akhir}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Stok Produk';
    }
}

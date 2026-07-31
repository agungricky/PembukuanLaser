<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdukPerformaExport implements FromArray, WithHeadings, WithStyles, WithEvents, ShouldAutoSize
{
    /**
     * @param array<int, array{0:string,1:string,2:int,3:float}> $rows
     */
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return ['Nama Produk', 'Variasi', 'Jumlah Terjual', 'Total Penjualan'];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $s = $event->sheet->getDelegate();
                $last = $s->getHighestRow();

                $s->freezePane('A2');
                $s->setAutoFilter("A1:D{$last}");

                if ($last >= 2) {
                    $s->getStyle("C2:C{$last}")
                      ->getNumberFormat()->setFormatCode('#,##0');
                    $s->getStyle("D2:D{$last}")
                      ->getNumberFormat()->setFormatCode('"Rp"* #,##0;[Red]"Rp"* -#,##0');
                }
            }
        ];
    }
}
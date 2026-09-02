<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class perprodukRegulerExport implements FromArray, WithEvents, WithTitle
{
    protected Collection $data;
    protected array $mergeSku = [];

    public function __construct(Collection $tabtwo)
    {
        $this->data = $tabtwo;
    }

    public function array(): array
    {
        $hasil = [];

        $hasil[] = ['SKU','ID PER PRODUK','STATUS',];
        $rowExcel = 2;
        foreach ($this->data as $sku => $items) {
            $awal = $rowExcel;
            foreach ($items as $index => $item) {
                $hasil[] = [
                    $index === 0 ? $sku : '',
                    $item['id_per_produk'],
                    '',
                ];

                $rowExcel++;
            }

            $akhir = $rowExcel - 1;
            if ($akhir > $awal) {
                $this->mergeSku[] = [
                    'awal' => $awal,
                    'akhir' => $akhir,
                ];
            }
        }

        return $hasil;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:C1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => [
                            'ARGB' => 'FFFFFFFF',
                        ],
                    ],

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'ARGB' => 'FF343A40',
                        ],
                    ],

                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | MERGE SKU YANG SAMA
                |--------------------------------------------------------------------------
                */
                foreach ($this->mergeSku as $merge) {

                    $awal = $merge['awal'];
                    $akhir = $merge['akhir'];

                    $sheet->mergeCells(
                        "A{$awal}:A{$akhir}"
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | ALIGNMENT SKU
                |--------------------------------------------------------------------------
                */
                $akhirData = $sheet->getHighestRow();

                $sheet->getStyle("A2:A{$akhirData}")
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    )
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );

                /*
                |--------------------------------------------------------------------------
                | STATUS OTOMATIS DARI TAB 1
                |--------------------------------------------------------------------------
                |
                | Tab 1:
                | A = SKU
                | E = STATUS
                |
                */
                for ($row = 2; $row <= $akhirData; $row++) {

                    $formula =
                        '=XLOOKUP('.
                            'LOOKUP(2,1/($A$2:A'.$row.'<>""),$A$2:A'.$row.'),'.
                            "'Order Reguler'!\$A:\$A,".
                            "'Order Reguler'!\$E:\$E,".
                            '""'.
                        ')';

                    $sheet->setCellValue(
                        "C{$row}",
                        $formula
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | BORDER
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle("A1:C{$akhirData}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        Border::BORDER_THIN
                    );

                /*
                |--------------------------------------------------------------------------
                | COLUMN WIDTH
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')
                    ->setWidth(20);

                $sheet->getColumnDimension('B')
                    ->setWidth(20);

                $sheet->getColumnDimension('C')
                    ->setWidth(18);

                /*
                |--------------------------------------------------------------------------
                | ROW HEIGHT
                |--------------------------------------------------------------------------
                */
                $sheet->getRowDimension(1)
                    ->setRowHeight(25);
            },
        ];
    }

    public function title(): string
    {
        return 'Detail Reguler';
    }
}

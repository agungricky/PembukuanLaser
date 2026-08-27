<?php

namespace App\Exports;

use App\Models\Produk;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class produkExport implements FromArray, WithEvents, WithTitle
{
    protected $produk;

    protected $namaFile;

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
        $this->produk = Produk::with('kategori')
            ->where('status', 'aktif')
            ->when($id != 0, function ($query) use ($id) {
                $query->where('kategori_id', $id);
            })
            ->get();
    }

    public function array(): array
    {
        $data = [
            ['', '', '', '', '', ''], // A1 - F1
            ['', '', '', '', '', ''], // A2 - F2
            ['SKU', 'NAMA PRODUK', 'VARIASI', 'HPP', 'STATUS', 'KATEGORI'], // A3 - F3
        ];

        foreach ($this->produk as $item) {
            $data[] = [
                $item->sku ?? '-',         // Kolom A
                $item->nama_produk ?? '-', // Kolom B
                $item->variasi ?? '-',     // Kolom C
                $item->hpp ?? '-',         // Kolom D
                $item->status ?? '-',       // Kolom E
                $item->kategori->nama_kategori ?? '-',       // Kolom F
            ];
        }

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Header A1 - F2
                $sheet->mergeCells('A1:F2');
                if ($this->id == 0) {
                    $sheet->setCellValue('A1', 'EXPORT PRODUK - Semua Produk'.' - '.now()->format('d/m/Y'));
                } else {
                    $sheet->setCellValue(
                        'A1',
                        'EXPORT PRODUK - '
                        .($this->produk[0]->kategori->nama_kategori ?? '-')
                        .' - '
                        .now()->format('d/m/Y')
                    );
                }
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(20);

                // Header Title kolom A3-F3
                $sheet->setCellValue('A3', 'SKU');
                $sheet->setCellValue('B3', 'NAMA PRODUK');
                $sheet->setCellValue('C3', 'VARIASI');
                $sheet->setCellValue('D3', 'HPP');
                $sheet->setCellValue('E3', 'STATUS');
                $sheet->setCellValue('F3', 'KATEGORI');
                $sheet->getStyle('A3:F3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $jumlahProduk = count($this->produk);
                $awal = 4;
                $akhir = 3 + $jumlahProduk;
                $sheet->getStyle("A{$awal}:F{$akhir}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->getStyle("C{$awal}:E{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // Kolom A - E Lebarnya menyesuaikan konten
                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('E')->setAutoSize(true);
                $sheet->getColumnDimension('F')->setAutoSize(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Produk';
    }
}

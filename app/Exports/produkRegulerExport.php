<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class produkRegulerExport implements FromArray, WithEvents, WithTitle
{
    private $data;

    private $jumlahdata;

    public function __construct($tabone)
    {
        $this->data = $tabone;
        $this->jumlahdata = count($tabone);
    }

    public function array(): array
    {
        $row = [
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['Tanggal Export : '.now()->format('d/m/Y H:i'), 'Export By : '.(Auth::user()?->name ?? '-'), '', '', ''],
            ['SKU', 'Nama Produk', 'Variasi', 'Kebutuhan Produksi', 'Status Produksi'],
        ];
        foreach ($this->data as $item) {
            $row[] = [
                'SKU' => $item['sku'] ?? '-',
                'Nama Produk' => $item['nama_produk'] ?? '-',
                'Variasi' => $item['variasi'] ?? '-',
                'Kebutuhan Produksi' => $item['kebutuhan_produksi'] ?? 0,
                'Status Produksi' => 'BELUM',
            ];
        }

        return $row;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Header formatting A1:E1
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', "DAFTAR PESANAN PRODUK NON CUSTOM");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(50);

                // Header formatting A3:B3
                $sheet->mergeCells('A2:E2');
                $sheet->setCellValue('A2', "Jangan berikan file export ini kepada operator produksi lain. 1 file export hanya untuk 1 operator produksi.");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => false, 'size' => 12],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                ]);
                
                $sheet->getStyle('A3:B3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '133458'],
                    ],
                    'alignment' => ['horizontal' => 'start', 'vertical' => 'center', 'wrapText' => true],
                ]);

                // Header formatting A4:E4
                $sheet->getStyle('A4:E4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // Warna teks putih
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
                ]);

                $sheet->mergeCells('C3:C4');
                $sheet->setCellValue('C3', 'Variasi');
                $sheet->mergeCells('D3:D4');
                $sheet->setCellValue('D3', 'Kebutuhan Produksi');
                $sheet->mergeCells('E3:E4');
                $sheet->setCellValue('E3', 'Status Produksi');

                $sheet->getStyle('C3:E4')->applyFromArray([
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
                ]);

                $awal = 4;
                $akhir = $awal + $this->jumlahdata;
                // Pengaturan posisi A
                $sheet->getStyle("A{$awal}:A{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'start',
                        'vertical' => 'center',
                    ],
                ]);

                // Pengaturan posisi B
                $sheet->getStyle("B{$awal}:B{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'start',
                        'vertical' => 'center',
                    ],
                ]);

                // Pengaturan posisi C
                $sheet->getStyle("C{$awal}:C{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'start',
                        'vertical' => 'center',
                    ],
                ]);

                // Pengaturan posisi D
                $sheet->getStyle("D{$awal}:D{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // Pengaturan posisi E
                $sheet->getStyle("E{$awal}:E{$akhir}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                $sheet->getStyle("A1:E{$akhir}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Logic Kolom E jadi select dan warna
                for ($row = $awal; $row <= $akhir; $row++) {
                    $validation = $sheet->getCell("E{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Status Tidak Valid');
                    $validation->setError('Status hanya boleh BELUM atau SELESAI.');
                    $validation->setFormula1('"BELUM,SELESAI"');
                }

                // BELUM = MERAH + PUTIH
                $belum = new Conditional;
                $belum->setConditionType(Conditional::CONDITION_CELLIS);
                $belum->setOperatorType(Conditional::OPERATOR_EQUAL);
                $belum->addCondition('"BELUM"');
                // background merah
                $belum->getStyle()
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID);
                $belum->getStyle()
                    ->getFill()
                    ->getStartColor()
                    ->setARGB('FFFF0000');

                // Text putih
                $belum->getStyle()
                    ->getFont()
                    ->getColor()
                    ->setARGB(Color::COLOR_WHITE);

                // SELESAI = HIJAU + PUTIH
                $selesai = new Conditional;
                $selesai->setConditionType(Conditional::CONDITION_CELLIS);
                $selesai->setOperatorType(Conditional::OPERATOR_EQUAL);
                $selesai->addCondition('"SELESAI"');
                // Background hijau
                $selesai->getStyle()
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID);

                $selesai->getStyle()
                    ->getFill()
                    ->getStartColor()
                    ->setARGB('FF008000');

                // Text putih
                $selesai->getStyle()
                    ->getFont()
                    ->getColor()
                    ->setARGB(Color::COLOR_WHITE);

                // Terapkan conditional formatting ke kolom E
                $sheet
                    ->getStyle("E{$awal}:E{$akhir}")
                    ->setConditionalStyles([
                        $belum,
                        $selesai,
                    ]);

                // Kolom A - E Lebarnya menyesuaikan konten
                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('E')->setAutoSize(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Produksi Reguler';
    }
}

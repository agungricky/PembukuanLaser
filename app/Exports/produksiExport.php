<?php

namespace App\Exports;

use App\Models\PesananPerProduk;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class produksiExport implements FromArray, WithEvents, WithTitle
{
    protected $data;
    protected $page;
    protected $exporter_id;
    protected $jumlahData;

    public function __construct($page, $exporter_id)
    {
        $this->data = PesananPerProduk::with('produk.stok_produk')
            ->where('exporter_id', $exporter_id->id)
            ->get()
            ->groupBy('sku')
            ->map(function ($items, $sku) {
                $first = $items->first();
                $jumlahPesanan = $items->sum('jumlah');
                $stok = $first->produk?->stok_produk?->jumlah_tersedia ?? 0;

                return [
                    'sku' => $sku,
                    'nama_produk' => $first->produk?->nama_produk ?? '-',
                    'variasi' => $first->produk?->variasi ?? '-',
                    'jumlah_pesanan' => $jumlahPesanan,
                    'stok' => $stok,
                    'kebutuhan_produksi' => max(0, $jumlahPesanan - $stok),
                ];
            })
            ->values();
            
        $this->exporter_id = $exporter_id;

        if ($page == 'reguler') {
            $this->page = "DAFTAR ANTRIAN PRODUKSI PRODUK REGULER";
        }elseif ($page == 'stok') {
            $this->page = "DAFTAR ANTRIAN PRODUKSI PRODUK STOK MENIPIS";
        }

        $this->jumlahData = $this->data->count();

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
                $sheet->mergeCells('A1:E2');
                $sheet->setCellValue('A1', $this->page);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(50);

                $sheet->mergeCells('B3:E3');
                $sheet->getStyle('A3:E3')->applyFromArray([
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

                $sheet->setCellValue('C4', 'Variasi');
                $sheet->setCellValue('D4', 'Kebutuhan Produksi');
                $sheet->setCellValue('E4', 'Status Produksi');

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
                $akhir = $awal + $this->jumlahData;
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

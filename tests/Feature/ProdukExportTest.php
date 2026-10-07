<?php

namespace Tests\Feature;

use App\Exports\produkExport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ProdukExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->softDeletes();
        });
        Schema::create('produk', function (Blueprint $table) {
            $table->string('sku')->primary();
            $table->string('nama_produk');
            $table->string('variasi');
            $table->decimal('hpp', 10, 2);
            $table->string('status');
            $table->unsignedBigInteger('kategori_id');
        });

        DB::table('kategoris')->insert([
            ['id' => 1, 'nama_kategori' => 'Kategori A'],
            ['id' => 2, 'nama_kategori' => 'Kategori B'],
        ]);
        foreach (['00001', '9999', '10000'] as $index => $sku) {
            DB::table('produk')->insert([
                'sku' => $sku,
                'nama_produk' => 'Produk '.$sku,
                'variasi' => '-',
                'hpp' => 15000,
                'status' => $index === 0 ? 'nonaktif' : 'aktif',
                'kategori_id' => $index === 2 ? 2 : 1,
            ]);
        }
    }

    public function test_xlsx_keeps_all_statuses_and_exact_sku_values(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'produk-export-');

        try {
            file_put_contents($file, Excel::raw(new produkExport(0), \Maatwebsite\Excel\Excel::XLSX));
            $workbook = IOFactory::load($file);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame(6, $sheet->getHighestDataRow());

            $actual = [];
            for ($row = 4; $row <= 6; $row++) {
                $cell = $sheet->getCell('A'.$row);
                $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('D'.$row)->getDataType());
                $actual[$cell->getValue()] = $sheet->getCell('E'.$row)->getValue();
            }
            $this->assertSame('nonaktif', $actual['00001']);
            $this->assertSame('aktif', $actual['9999']);
            $this->assertSame('aktif', $actual['10000']);
            $workbook->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    public function test_category_filter_still_includes_inactive_products(): void
    {
        $rows = array_slice((new produkExport(1))->array(), 3);

        $this->assertEqualsCanonicalizing(['00001', '9999'], array_column($rows, 0));
    }

    public function test_export_preserves_every_product_across_thousand_row_chunks(): void
    {
        $products = [];
        for ($index = 1; $index <= 2100; $index++) {
            $products[] = [
                'sku' => 'EMB'.str_pad($index, 4, '0', STR_PAD_LEFT),
                'nama_produk' => 'Produk '.$index,
                'variasi' => '-',
                'hpp' => 0,
                'status' => 'aktif',
                'kategori_id' => 2,
            ];
        }
        foreach (array_chunk($products, 100) as $chunk) {
            DB::table('produk')->insert($chunk);
        }

        $export = new produkExport(2);
        $expected = $export->array();
        $file = tempnam(sys_get_temp_dir(), 'produk-export-');
        try {
            file_put_contents($file, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $workbook = IOFactory::load($file);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame(count($expected), $sheet->getHighestDataRow());
            foreach (array_slice($expected, 3) as $index => $product) {
                $this->assertSame($product[0], $sheet->getCell('A'.($index + 4))->getValue());
                $this->assertEquals($product[3], $sheet->getCell('D'.($index + 4))->getValue());
            }
            $workbook->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Services\ImportResi\TikTokService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TikTokResiImportTest extends TestCase
{
    public function test_large_store_context_keeps_queries_bounded_and_detects_receipts(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->string('no_pesanan')->primary();
            $table->string('no_resi');
            $table->unsignedInteger('id_toko');
        });
        Schema::create('resi_pages', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan');
        });

        for ($batch = 0; $batch < 66; $batch++) {
            $orders = [];
            for ($index = 0; $index < 1000; $index++) {
                $orders[] = [
                    'no_pesanan' => (string) (581406001429841262 + $batch * 1000 + $index),
                    'no_resi' => 'JY'.($batch * 1000 + $index + 10000000),
                    'id_toko' => 1,
                ];
            }
            DB::table('pesanan')->insert($orders);
        }
        DB::table('pesanan')->insert([
            'no_pesanan' => '581406001429999999', 'no_resi' => 'JY99999999', 'id_toko' => 2,
        ]);
        DB::table('resi_pages')->insert([
            ['no_pesanan' => '581406001429841262'],
            ['no_pesanan' => '581406001429841262'],
            ['no_pesanan' => '581406001429999999'],
        ]);

        DB::enableQueryLog();
        $service = new TikTokService;
        $service->prepareContext(1);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(2, $queries);
        foreach ($queries as $query) {
            $this->assertSame([1], $query['bindings']);
        }
        $this->assertSame('existing', $service->detectPage('Order ID: 581406001429841262', 1)['status']);
        $this->assertSame('matched', $service->detectPage('Order ID: 581406001429907261', 1)['status']);
        $this->assertSame('matched', $service->detectPage('Tracking ID: JY10000001', 1)['status']);
        $this->assertSame('not_found', $service->detectPage('Order ID: 581406001429999999', 1)['status']);

        $service->prepareContext(2);
        $this->assertSame('existing', $service->detectPage('Order ID: 581406001429999999', 2)['status']);
        $service->prepareContext(3);
        $this->assertSame('not_found', $service->detectPage('Order ID: 581406001429841262', 3)['status']);
    }
}

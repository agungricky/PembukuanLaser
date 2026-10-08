<?php

namespace Tests\Feature;

use App\Services\ImportResi\DeadlineUpdater;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResiDeadlineUpdaterTest extends TestCase
{
    public function test_updates_deadlines_in_batches_preserving_store_and_page_rules(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->string('no_pesanan')->primary();
            $table->integer('id_toko');
            $table->string('batas_kirim_at')->nullable();
            $table->string('batas_kirim_source')->nullable();
            $table->string('batas_kirim_raw')->nullable();
        });

        $pages = [];
        for ($index = 0; $index < 601; $index++) {
            $order = (string) (581406001429841262 + $index);
            DB::table('pesanan')->insert([
                'no_pesanan' => $order, 'id_toko' => 1, 'batas_kirim_raw' => 'lama',
            ]);
            $pages[] = [
                'no_pesanan' => $order,
                'batas_kirim_at' => '2026-10-09 23:59:59',
                'batas_kirim_source' => 'tiktok_in_transit_by',
                'batas_kirim_raw' => "Ship by: 09/10/2026 ' ?",
            ];
        }
        DB::table('pesanan')->insert([
            ['no_pesanan' => 'other-store', 'id_toko' => 2, 'batas_kirim_raw' => 'lama'],
            ['no_pesanan' => 'without-deadline', 'id_toko' => 1, 'batas_kirim_raw' => 'lama'],
        ]);
        $pages[] = ['no_pesanan' => 'other-store', 'batas_kirim_at' => '2026-10-09 23:59:59'];
        $pages[] = ['no_pesanan' => 'without-deadline', 'batas_kirim_at' => null];
        $pages[] = ['no_pesanan' => 'missing-order', 'batas_kirim_at' => '2026-10-09 23:59:59'];
        $pages[] = ['no_pesanan' => '581406001429841262', 'batas_kirim_at' => '2026-10-10 23:59:59'];

        DB::enableQueryLog();
        DB::transaction(fn () => (new DeadlineUpdater)->update(1, $pages));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(2, $queries);
        foreach ($queries as $query) {
            $this->assertLessThanOrEqual(3501, count($query['bindings']));
        }
        $first = DB::table('pesanan')->where('no_pesanan', '581406001429841262')->first();
        $this->assertSame('2026-10-10 23:59:59', $first->batas_kirim_at);
        $this->assertNull($first->batas_kirim_source);
        $this->assertNull($first->batas_kirim_raw);
        $this->assertSame(600, DB::table('pesanan')->where('batas_kirim_at', '2026-10-09 23:59:59')->count());
        $this->assertSame("Ship by: 09/10/2026 ' ?", DB::table('pesanan')->where('no_pesanan', '581406001429841862')->value('batas_kirim_raw'));
        foreach (['other-store', 'without-deadline'] as $order) {
            $this->assertSame('lama', DB::table('pesanan')->where('no_pesanan', $order)->value('batas_kirim_raw'));
        }
        $this->assertSame(603, DB::table('pesanan')->count());

        DB::flushQueryLog();
        DB::enableQueryLog();
        (new DeadlineUpdater)->update(1, [['no_pesanan' => 'without-deadline']]);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }
}

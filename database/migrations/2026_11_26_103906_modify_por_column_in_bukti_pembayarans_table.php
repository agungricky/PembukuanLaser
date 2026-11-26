<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->decimal('por', 15, 2)
                ->nullable()
                ->default(0)
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('bukti_pembayarans')
            ->whereNull('por')
            ->update(['por' => 0]);

        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->decimal('por', 15, 2)
                ->nullable(false)
                ->default(0)
                ->change();
        });
    }
};

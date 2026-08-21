<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dateTime('batas_kirim_at')->nullable()->index();
            $table->string('batas_kirim_source', 30)->nullable();
            $table->string('batas_kirim_raw', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn([
                'batas_kirim_at',
                'batas_kirim_source',
                'batas_kirim_raw',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropColumn('status_produksi');
            $table->foreignId('tracking')
                ->nullable()
                ->after('status_pesanan')
                ->constrained('exporters')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropForeign(['tracking']);
            $table->dropColumn('tracking');
            $table->tinyInteger('status_produksi')->default(0);
        });
    }
};

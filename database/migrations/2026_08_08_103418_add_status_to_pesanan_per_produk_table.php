<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            // 0 Pesanan Baru masuk belum diproses
            // 1 Pesanan Sudah ditangani oleh admin gudang
            $table->enum('status_pesanan', [0, 1])->default(0)->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropColumn('status_pesanan');
        });
    }
};

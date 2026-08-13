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
            $table->foreignId('mutasi_stok_id')
                ->nullable()
                ->after('status_pesanan')
                ->constrained('mutasi_stoks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropForeign(['mutasi_stok_id']);
            $table->dropColumn('mutasi_stok_id');
        });
    }
};

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
            $table->boolean('produksi')
                ->default(false)
                ->after('status_pesanan');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropColumn('produksi');
        });
    }
};

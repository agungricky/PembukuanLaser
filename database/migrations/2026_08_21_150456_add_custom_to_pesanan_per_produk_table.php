<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->boolean('custom')
                ->default(false)
                ->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropColumn('custom');
        });
    }
};

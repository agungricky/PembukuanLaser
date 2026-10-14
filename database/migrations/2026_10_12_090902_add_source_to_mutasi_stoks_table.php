<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->enum('source', ['stok', 'produksi'])
                ->nullable()
                ->after('stok_produk_id');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};

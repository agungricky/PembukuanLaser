<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropColumn('produksi');
        });

        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->foreignId('produksi_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::statement("
            ALTER TABLE mutasi_stoks
            MODIFY jenis_mutasi ENUM(
                'edit', 
                'masuk', 
                'keluar', 
                'siap', 
                'sampel',
                'produksi'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE mutasi_stoks
            MODIFY jenis_mutasi ENUM(
                'edit', 
                'masuk', 
                'keluar', 
                'siap', 
                'sampel'
            ) NOT NULL
        ");

        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->dropForeign(['produksi_id']);
            $table->dropColumn('produksi_id');
        });

        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->boolean('produksi')
                ->default(false);
        });
    }
};

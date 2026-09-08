<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah processing dan exporter_id ke stok_produks
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->unsignedInteger('processing')
                ->nullable()
                ->after('min_stok');

            $table->foreignId('exporter_id')
                ->nullable()
                ->after('processing')
                ->constrained('exporters')
                ->nullOnDelete();
        });

        // Rename tracking menjadi exporter_id
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->renameColumn('tracking', 'exporter_id');
        });

        // Pastikan tipe sama dengan exporters.id lalu buat relasi
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->unsignedBigInteger('exporter_id')
                ->nullable()
                ->change();

            $table->foreign('exporter_id')
                ->references('id')
                ->on('exporters')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Hapus relasi pesanan_per_produk
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropForeign(['exporter_id']);
        });

        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->renameColumn('exporter_id', 'tracking');
        });

        // Hapus kolom dari stok_produks
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exporter_id');
            $table->dropColumn('processing');
        });
    }
};

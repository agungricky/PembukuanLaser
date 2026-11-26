<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 01. HAPUS FOREIGN KEY
        Schema::table('pengajuan_edit_pesanans', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });

        // 02. HAPUS KOLOM
        Schema::table('pengajuan_edit_pesanans', function (Blueprint $table) {
            $table->dropColumn([
                'pesanan_perproduk',
                'reviewed_by',
                'reviewed_at',
                'catatan_review',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_edit_pesanans', function (Blueprint $table) {
            $table->json('pesanan_perproduk');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('catatan_review')->nullable();
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};

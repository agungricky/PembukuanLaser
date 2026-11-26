<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->enum('jenis_transaksi', [
                'cod',
                'non-cod',
                'dp',
                'pelunasan',
                'por',
            ])->change();
        });

        // 02. UBAH DATA PELUNASAN MENJADI POR
        DB::table('bukti_pembayarans')
            ->where('jenis_transaksi', 'pelunasan')
            ->update([
                'jenis_transaksi' => 'por',
            ]);

        // 03. HAPUS ENUM PELUNASAN
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->enum('jenis_transaksi', [
                'cod',
                'non-cod',
                'dp',
                'por',
            ])->change();
        });
    }

    public function down(): void
    {
        // 01. KEMBALIKAN ENUM PELUNASAN
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->enum('jenis_transaksi', [
                'cod',
                'non-cod',
                'dp',
                'pelunasan',
                'por',
            ])->change();
        });

        // 02. KEMBALIKAN DATA POR MENJADI PELUNASAN
        DB::table('bukti_pembayarans')
            ->where('jenis_transaksi', 'por')
            ->update([
                'jenis_transaksi' => 'pelunasan',
            ]);

        // 03. HAPUS ENUM POR
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->enum('jenis_transaksi', [
                'cod',
                'non-cod',
                'dp',
                'pelunasan',
            ])->change();
        });
    }
};

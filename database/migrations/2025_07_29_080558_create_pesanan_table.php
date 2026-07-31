<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->string('no_pesanan', 50)->primary();
            $table->date('tanggal')->nullable();
            $table->string('no_resi', 100)->nullable();
            $table->unsignedBigInteger('id_toko');
            $table->unsignedBigInteger('id_user');
            $table->string('nama_pembeli', 100)->nullable();
            $table->string('kurir', 50)->nullable();
            $table->enum('status', ['proses', 'kirim', 'selesai', 'return', 'pengembalian', 'batal'])->default('proses');
            $table->decimal('total_hpp', 10, 2)->nullable();
            $table->decimal('total_harga', 10, 2)->nullable();
            $table->decimal('total_admin', 10, 2)->nullable();
            $table->decimal('pencairan', 10, 2)->nullable();
            $table->string('notes', 255)->nullable();

            $table->foreign('id_toko')->references('id_toko')->on('toko');
            $table->foreign('id_user')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};

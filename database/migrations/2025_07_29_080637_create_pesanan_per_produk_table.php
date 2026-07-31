<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pesanan_per_produk', function (Blueprint $table) {
            $table->id('id_per_produk');
            $table->string('no_pesanan', 50);
            $table->string('nama_produk', 255);
            $table->string('variasi', 100)->nullable();
            $table->integer('jumlah');
            $table->decimal('hpp', 10, 2)->nullable();
            $table->decimal('harga', 10, 2)->nullable();

            $table->foreign('no_pesanan')->references('no_pesanan')->on('pesanan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_per_produk');
    }
};
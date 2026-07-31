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
        Schema::create('iklan', function (Blueprint $table) {
            $table->string('no_iklan', 50)->primary();
            $table->date('tanggal');
            $table->unsignedBigInteger('id_toko');
            $table->decimal('jumlah_pembayaran', 10, 2);
            $table->decimal('saldo', 10, 2);
            $table->string('metode_pembayaran', 50);

            $table->foreign('id_toko')->references('id_toko')->on('toko')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iklan');
    }
};

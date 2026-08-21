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
        Schema::create('returs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('per_produk_id');
            $table->integer('diterima')->default(0);
            $table->timestamps();

            $table->foreign('per_produk_id')
                ->references('id_per_produk')
                ->on('pesanan_per_produk')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('req_fotos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('per_produk_id');

            $table->foreign('per_produk_id')
                ->references('id_per_produk')
                ->on('pesanan_per_produk')
                ->cascadeOnDelete();

            $table->string('foto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('req_fotos');
    }
};

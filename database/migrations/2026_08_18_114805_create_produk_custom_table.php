<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_customs', function (Blueprint $table) {
            $table->id();
            $table->string('sku_id');

            $table->foreign('sku_id')
                ->references('sku')
                ->on('produk')
                ->cascadeOnDelete();

            $table->decimal('harga_jual', 15, 2);
            $table->string('nama_produk', 100);
            $table->integer('jumlah');
            $table->enum('status', ['masuk', 'keluar']);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_customs');
    }
};

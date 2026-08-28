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
        Schema::dropIfExists('produk_customs');
    }

    public function down(): void
    {
        Schema::create('produk_customs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id');
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->string('nama_produk');
            $table->integer('jumlah')->default(1);
            $table->string('status')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }
};

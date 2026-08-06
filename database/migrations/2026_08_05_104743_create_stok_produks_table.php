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
        Schema::create('stok_produks', function (Blueprint $table) {
            $table->id();
            $table->string('sku_id'); 
            $table->foreign('sku_id')->references('sku')->on('produk')->onDelete('cascade');
            $table->integer('jumlah_tersedia')->default(0);
            $table->integer('min_stok')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_produks');
    }
};

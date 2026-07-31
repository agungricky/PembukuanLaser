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
        Schema::create('toko', function (Blueprint $table) {
            $table->id('id_toko');
            $table->string('nama_toko', 100)->unique();
            $table->decimal('biaya_admin', 10, 2)->nullable();
            $table->decimal('biaya_tambahan', 10, 2)->nullable();
            $table->enum('marketplace', ['Shopee', 'Tiktok']);
            $table->timestamps(); // opsional, bisa dihapus kalau tidak perlu created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toko');
    }
};

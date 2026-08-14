<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resi_imports', function (Blueprint $table) {
            $table->id();

            $table->string('nama_file');
            $table->string('path_file');

            $table->unsignedInteger('jumlah_halaman')->default(0);

            $table->enum('marketplace', [
                'Shopee',
                'TikTok'
            ]);

            $table->unsignedBigInteger('id_toko')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->timestamps();

            $table->foreign('id_toko')
                ->references('id_toko')
                ->on('toko')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resi_imports');
    }
};

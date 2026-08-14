<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_per_produk')->unique();

            $table->string('plat_lengkap')->nullable();
            $table->string('nama')->nullable();

            $table->string('tanggal_bulan_tahun')->nullable();

            $table->integer('jumlah_editor')->nullable();

            $table->boolean('tanpa_heartbeat')->default(false);
            $table->boolean('tanpa_korlantas')->default(false);

            // Versi yang sudah dinormalisasi untuk search packing
            $table->string('request_search')->nullable()->index();

            $table->unsignedBigInteger('editor_imported_by')->nullable();
            $table->dateTime('editor_imported_at')->nullable();

            $table->timestamps();

            $table->foreign('id_per_produk')
                ->references('id_per_produk')
                ->on('pesanan_per_produk')
                ->cascadeOnDelete();

            $table->foreign('editor_imported_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_requests');
    }
};

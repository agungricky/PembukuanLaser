<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->dropForeign(
                'pesanan_per_produk_exporter_id_foreign'
            );

            $table->dropForeign(
                'pesanan_per_produk_tracking_foreign'
            );

            $table->dropColumn('exporter_id');
        });

        Schema::dropIfExists('exporters');
    }

    public function down(): void
    {
        Schema::create('exporters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('status', [
                'proses',
                'selesai',
            ])->default('proses');

            $table->timestamps();
        });

        Schema::table('pesanan_per_produk', function (Blueprint $table) {
            $table->unsignedBigInteger('exporter_id')->nullable();
        });
    }
};

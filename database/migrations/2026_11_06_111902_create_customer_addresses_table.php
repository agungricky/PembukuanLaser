<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan')->unique();
            $table->string('provinsi')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa')->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();

            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_hapus_pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan')->index();
            $table->text('keterangan');
            $table->enum('status', ['pending', 'approve', 'reject'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_hapus_pesanans');
    }
};

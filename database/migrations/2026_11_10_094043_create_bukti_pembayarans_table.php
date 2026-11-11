<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukti_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan');
            $table->enum('jenis_transaksi', ['cod', 'non-cod', 'dp', 'pelunasan']);
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('kekurangan')->default(0);
            $table->string('bukti_bayar');
            $table->timestamps();

            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_pembayarans');
    }
};

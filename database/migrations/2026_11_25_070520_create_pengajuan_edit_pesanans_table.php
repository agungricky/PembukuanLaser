<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_edit_pesanans', function (Blueprint $table) {
            $table->id();

            // 01. DATA PESANAN
            $table->string('no_pesanan');
            $table->dateTime('tanggal_order');
            $table->string('nama_pembeli', 255);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('total_hpp')->default(0);
            $table->unsignedBigInteger('total_harga')->default(0);
            $table->unsignedBigInteger('total_admin')->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('chat_masuk_at');

            // 02. ALAMAT PENGIRIMAN
            $table->string('provinsi');
            $table->string('kabupaten_kota');
            $table->string('kecamatan');
            $table->string('desa')->nullable();
            $table->text('alamat_lengkap');
            $table->string('phone', 15);

            // 03. PEMBAYARAN
            $table->enum('jenis_transaksi', [
                'cod',
                'non-cod',
                'dp',
                'por',
            ]);

            $table->decimal('cod', 15, 2)->nullable();
            $table->decimal('non_cod', 15, 2)->nullable();
            $table->decimal('dp', 15, 2)->nullable();
            $table->decimal('por', 15, 2)->nullable();
            $table->string('bukti_bayar')->nullable();

            // 04. PESANAN PER PRODUK DAN FOTO PRODUK
            $table->json('pesanan_perproduk');

            // 05. STATUS PENGAJUAN
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('catatan_review')->nullable();

            $table->timestamps();

            // 06. FOREIGN KEY
            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // 07. INDEX
            $table->index(['no_pesanan', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_edit_pesanans');
    }
};
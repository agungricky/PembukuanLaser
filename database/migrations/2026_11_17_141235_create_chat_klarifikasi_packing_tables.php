<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel utama percakapan Packing dan CS
        Schema::create('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->id();

            $table->string('no_pesanan', 50)->index();

            $table->unsignedBigInteger('id_per_produk')->nullable()->index();

            $table->unsignedBigInteger('packing_user_id')->index();

            $table->unsignedBigInteger('cs_user_id')->index();

            $table->string('topik', 255);

            $table->enum('status', [
                'menunggu_jawaban',
                'dibalas',
                'selesai'
            ])->default('menunggu_jawaban');

            $table->timestamp('pesan_terakhir_at')->nullable();

            $table->timestamps();

            $table->index(['cs_user_id', 'status']);
            $table->index(['packing_user_id', 'status']);
        });

        // Tabel seluruh pesan chat
        Schema::create('pesan_chat_klarifikasi_packings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chat_id')
                ->constrained('chat_klarifikasi_packings')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('pengirim_user_id')->index();

            $table->text('pesan');

            $table->timestamp('dibaca_at')->nullable();

            $table->timestamps();

            $table->index(['chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan_chat_klarifikasi_packings');
        Schema::dropIfExists('chat_klarifikasi_packings');
    }
};

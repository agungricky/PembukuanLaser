<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_klarifikasi_merge_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_chat_id')->unique();
            $table->unsignedBigInteger('canonical_chat_id');
            $table->string('no_pesanan', 50)->index();
            $table->longText('original_chat_json');
            $table->unsignedInteger('jumlah_pesan_dipindahkan');
            $table->timestamp('merged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_klarifikasi_merge_archives');
    }
};

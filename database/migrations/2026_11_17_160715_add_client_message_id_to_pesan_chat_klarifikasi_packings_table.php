<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesan_chat_klarifikasi_packings', function (Blueprint $table) {
            $table->uuid('client_message_id')
                ->nullable()
                ->after('pengirim_user_id');

            $table->unique(
                ['chat_id', 'pengirim_user_id', 'client_message_id'],
                'chat_pesan_client_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pesan_chat_klarifikasi_packings', function (Blueprint $table) {
            $table->dropUnique('chat_pesan_client_unique');
            $table->dropColumn('client_message_id');
        });
    }
};

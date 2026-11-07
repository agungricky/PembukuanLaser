<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->enum('platform', ['marketplace', 'meta'])
                ->default('marketplace')
                ->after('no_pesanan');

            $table->dateTime('chat_masuk_at')
                ->nullable()
                ->after('tag');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn([
                'platform',
                'chat_masuk_at',
            ]);
        });
    }
};

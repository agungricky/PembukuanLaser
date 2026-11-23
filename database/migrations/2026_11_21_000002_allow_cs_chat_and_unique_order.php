<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('chat_klarifikasi_packings')
            ->select('no_pesanan')
            ->groupBy('no_pesanan')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(
                'Masih ada chat ganda pada no_pesanan '.$duplicate->no_pesanan.
                '. Jalankan php artisan chat:merge-duplicates terlebih dahulu.'
            );
        }

        Schema::table('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->unsignedBigInteger('packing_user_id')->nullable()->change();
        });

        Schema::table('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->unique('no_pesanan', 'chat_klarifikasi_no_pesanan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->dropUnique('chat_klarifikasi_no_pesanan_unique');
        });

        if (DB::table('chat_klarifikasi_packings')->whereNull('packing_user_id')->exists()) {
            // Jangan paksa NOT NULL dan merusak chat yang dibuat CS.
            // Biarkan kolom nullable hingga seluruh chat tanpa Packing ditangani.
            return;
        }

        Schema::table('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->unsignedBigInteger('packing_user_id')->nullable(false)->change();
        });
    }
};

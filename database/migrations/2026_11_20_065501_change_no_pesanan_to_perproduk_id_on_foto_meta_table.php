<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foto_metas', function (Blueprint $table) {
            $table->dropForeign(['no_pesanan']);
            $table->dropColumn('no_pesanan');
        });

        Schema::table('foto_metas', function (Blueprint $table) {
            $table->unsignedBigInteger('perproduk_id')
                ->nullable()
                ->after('id');

            $table->foreign('perproduk_id')
                ->references('id_per_produk')
                ->on('pesanan_per_produk')
                ->cascadeOnDelete();

            $table->unique('perproduk_id');
        });
    }

    public function down(): void
    {
        Schema::table('foto_metas', function (Blueprint $table) {
            $table->dropForeign(['perproduk_id']);
            $table->dropUnique(['perproduk_id']);
            $table->dropColumn('perproduk_id');
        });

        Schema::table('foto_metas', function (Blueprint $table) {
            $table->string('no_pesanan')->nullable();

            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan');
        });
    }
};

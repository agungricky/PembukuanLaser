<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stok_produks')
            ->where('jumlah_tersedia', '<', 0)
            ->update([
                'jumlah_tersedia' => 0
            ]);

        // Ubah kolom agar tidak bisa menerima nilai negatif
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_tersedia')
                ->default(0)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->integer('jumlah_tersedia')
                ->default(0)
                ->change();
        });
    }
};

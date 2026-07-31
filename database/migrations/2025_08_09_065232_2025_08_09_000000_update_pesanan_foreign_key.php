<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Hapus foreign key lama
            $table->dropForeign(['id_user']);

            // Tambahkan foreign key baru tanpa ON DELETE CASCADE
            $table->foreign('id_user')
                  ->references('id')
                  ->on('users')
                  ->restrictOnDelete(); // atau ->nullOnDelete() kalau mau NULL
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Kembalikan ke ON DELETE CASCADE
            $table->dropForeign(['id_user']);

            $table->foreign('id_user')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }
};

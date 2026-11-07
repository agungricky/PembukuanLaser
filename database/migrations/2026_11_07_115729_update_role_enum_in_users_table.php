<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'pegawai',
                'manager',
                'editor',
                'packing',
                'gudang',
                'produksi',
                'developer',
                'cs'
            ])->default('pegawai')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'pegawai',
                'manager',
                'editor',
                'packing',
                'gudang',
                'produksi',
                'developer',
            ])->default('pegawai')->change();
        });
    }
};

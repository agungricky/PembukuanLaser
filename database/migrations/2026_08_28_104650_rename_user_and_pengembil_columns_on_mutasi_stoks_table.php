<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->renameColumn('user_id', 'gudang_id');
            $table->renameColumn('pengambil_id', 'adm_penjualan_id');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->renameColumn('gudang_id', 'user_id');
            $table->renameColumn('adm_penjualan_id', 'pengambil_id');
        });
    }
};

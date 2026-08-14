<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM(
                'pegawai',
                'manager',
                'editor',
                'packing',
                'gudang'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM(
                'pegawai',
                'manager',
                'packing',
                'gudang'
            ) NOT NULL
        ");
    }
};

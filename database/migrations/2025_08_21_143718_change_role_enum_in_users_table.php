<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('pegawai','manager', 'packing') NOT NULL");
    }

    public function down(): void
    {
        // rollback ke kondisi semula
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager', 'packing') NOT NULL");
    }
};
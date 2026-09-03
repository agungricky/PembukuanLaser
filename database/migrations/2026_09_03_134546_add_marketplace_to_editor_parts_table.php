<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editor_parts', function (Blueprint $table) {
            $table->string('marketplace', 20)
                ->nullable()
                ->after('sesi');

            $table->index(
                ['tanggal_part', 'sesi', 'marketplace'],
                'editor_parts_tanggal_sesi_marketplace_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('editor_parts', function (Blueprint $table) {
            $table->dropIndex(
                'editor_parts_tanggal_sesi_marketplace_index'
            );

            $table->dropColumn('marketplace');
        });
    }
};

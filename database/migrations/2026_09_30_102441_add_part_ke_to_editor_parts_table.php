<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editor_parts', function (Blueprint $table) {
            $table->unsignedInteger('part_ke')
                ->nullable()
                ->after('nomor_part');

            $table->index(
                [
                    'tanggal_part',
                    'sesi',
                    'marketplace',
                    'part_ke',
                ],
                'editor_parts_antrian_part_ke_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('editor_parts', function (Blueprint $table) {
            $table->dropIndex(
                'editor_parts_antrian_part_ke_index'
            );

            $table->dropColumn('part_ke');
        });
    }
};

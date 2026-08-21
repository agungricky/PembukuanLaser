<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editor_requests', function (Blueprint $table) {
            $table->foreignId('editor_part_id')
                ->nullable()
                ->after('id_per_produk')
                ->constrained('editor_parts')
                ->nullOnDelete();

            $table->string(
                'status_request',
                20
            )
                ->default('normal')
                ->after('jumlah_editor');

            $table->timestamp(
                'locked_at'
            )->nullable();

            $table->foreignId(
                'locked_by'
            )
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                [
                    'status_request',
                    'locked_at',
                ],
                'editor_requests_status_lock_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('editor_requests', function (Blueprint $table) {
            $table->dropIndex(
                'editor_requests_status_lock_index'
            );

            $table->dropConstrainedForeignId(
                'locked_by'
            );

            $table->dropColumn(
                'locked_at'
            );

            $table->dropColumn(
                'status_request'
            );

            $table->dropConstrainedForeignId(
                'editor_part_id'
            );
        });
    }
};

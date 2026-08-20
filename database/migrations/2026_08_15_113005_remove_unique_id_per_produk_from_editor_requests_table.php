<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editor_requests', function (Blueprint $table) {
            $table->index(
                'id_per_produk',
                'editor_requests_id_per_produk_index'
            );
        });

        Schema::table('editor_requests', function (Blueprint $table) {
            $table->dropUnique(
                'editor_requests_id_per_produk_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('editor_requests', function (Blueprint $table) {
            $table->unique(
                'id_per_produk',
                'editor_requests_id_per_produk_unique'
            );
        });

        Schema::table('editor_requests', function (Blueprint $table) {
            $table->dropIndex(
                'editor_requests_id_per_produk_index'
            );
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_part_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('editor_part_id')
                ->constrained('editor_parts')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('id_per_produk');

            $table->string('sku', 50);

            $table->string(
                'kelompok_produksi',
                150
            );

            $table->unsignedInteger('jumlah_awal');

            $table->unsignedInteger(
                'jumlah_final'
            )->nullable();

            $table->unsignedInteger(
                'urutan'
            )->default(0);

            $table->string(
                'status',
                20
            )->default('pending');

            $table->timestamp(
                'processed_at'
            )->nullable();

            $table->timestamps();

            $table->foreign(
                'id_per_produk',
                'editor_part_items_id_per_produk_fk'
            )
                ->references('id_per_produk')
                ->on('pesanan_per_produk')
                ->cascadeOnDelete();

            $table->unique(
                [
                    'editor_part_id',
                    'id_per_produk',
                ],
                'editor_part_items_part_item_unique'
            );

            $table->index(
                [
                    'editor_part_id',
                    'kelompok_produksi',
                ],
                'editor_part_items_part_kelompok_index'
            );

            $table->index(
                [
                    'editor_part_id',
                    'status',
                ],
                'editor_part_items_part_status_index'
            );

            $table->index(
                'id_per_produk',
                'editor_part_items_id_per_produk_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'editor_part_items'
        );
    }
};

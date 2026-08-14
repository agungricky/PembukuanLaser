<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resi_pages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('resi_import_id');

            $table->string('no_pesanan', 50);
            $table->string('no_resi', 100)->nullable();

            $table->unsignedInteger('halaman');

            // kalau order punya 2 halaman
            $table->unsignedTinyInteger('urutan')->default(1);

            $table->timestamps();

            $table->foreign('resi_import_id')
                ->references('id')
                ->on('resi_imports')
                ->cascadeOnDelete();

            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();

            $table->index('no_pesanan');
            $table->index('no_resi');

            $table->unique([
                'resi_import_id',
                'halaman'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resi_pages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->timestamps();
        });

        Schema::table('produk', function (Blueprint $table) {
            $table->enum('status', ['aktif', 'nonaktif'])
                ->default('aktif')
                ->after('hpp');

            $table->foreignId('kategori_id')
                ->nullable()
                ->after('status')
                ->constrained('kategoris')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kategori_id');
            $table->dropColumn('status');
        });

        Schema::dropIfExists('kategori');
    }
};

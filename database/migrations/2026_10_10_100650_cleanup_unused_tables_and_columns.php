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
        // 1. Hapus tabel anak terlebih dahulu
        Schema::dropIfExists('kesalahans');

        // 2. Hapus tabel induk
        Schema::dropIfExists('role_kesalahans');

        // 3. Hapus tabel totalklik
        Schema::dropIfExists('totalklik');

        // 4. Hapus foreign key exporter_id
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->dropForeign('stok_produks_exporter_id_foreign');
        });

        // 5. Hapus kolom stok_produks
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->dropColumn([
                'processing',
                'exporter_id',
                'min_stok',
            ]);
        });

        // 6. Hapus kolom exporters
        Schema::table('exporters', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Kembalikan kolom stok_produks
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->boolean('processing')->default(false);
            $table->unsignedBigInteger('exporter_id')->nullable();
            $table->integer('min_stok')->default(0);
        });

        // 2. Kembalikan foreign key exporter_id
        Schema::table('stok_produks', function (Blueprint $table) {
            $table->foreign('exporter_id')
                ->references('id')
                ->on('exporters')
                ->nullOnDelete();
        });

        // 3. Kembalikan kolom exporters
        Schema::table('exporters', function (Blueprint $table) {
            $table->string('source_type')->nullable();
        });

        // 4. Kembalikan tabel totalklik
        Schema::create('totalklik', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // 5. Kembalikan tabel induk
        Schema::create('role_kesalahans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // 6. Kembalikan tabel anak
        Schema::create('kesalahans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};

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
        Schema::create('kesalahans', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan');
            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();

            $table->foreignId('kesalahan_id')
                ->constrained('role_kesalahans')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesalahans');
    }
};

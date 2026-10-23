<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pencairans', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan');
            $table->foreignId('user_id')->nullable();
            $table->dateTime('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();

            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['no_pesanan', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pencairans');
    }
};

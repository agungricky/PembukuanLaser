<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komplains', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan');
            $table->unsignedBigInteger('user_id');
            $table->string('path', 500);
            $table->text('keterangan')->nullable();
            $table->enum('status', ['approve', 'reject', 'pending'])->default('pending');
            $table->timestamps();

            $table->foreign('no_pesanan')
                ->references('no_pesanan')
                ->on('pesanan')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komplains');
    }
};

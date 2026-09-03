<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exporters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('status', [
                'proses',
                'selesai'
            ])->default('proses');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exporters');
    }
};

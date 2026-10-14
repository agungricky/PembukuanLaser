<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progres_pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan');
            
            $table->foreign('no_pesanan')
            ->references('no_pesanan')
            ->on('pesanan')
            ->cascadeOnDelete();

            $table->string('role');
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_pesanans');
    }
};

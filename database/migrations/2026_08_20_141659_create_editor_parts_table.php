<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_parts', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_part');
            $table->unsignedInteger('nomor_part');
            $table->string('kode_part', 30)->unique();
            $table->unsignedSmallInteger('kapasitas_per_kelompok')->default(52);
            $table->string('status', 20)->default('open');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('downloaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('downloaded_at')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['tanggal_part', 'nomor_part'],
                'editor_parts_tanggal_nomor_unique'
            );

            $table->index(
                ['tanggal_part', 'status'],
                'editor_parts_tanggal_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_parts');
    }
};

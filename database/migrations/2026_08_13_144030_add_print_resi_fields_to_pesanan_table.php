<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dateTime('resi_printed_at')
                ->nullable()
                ->after('no_resi');

            $table->dateTime('resi_last_printed_at')
                ->nullable()
                ->after('resi_printed_at');

            $table->unsignedBigInteger('resi_printed_by')
                ->nullable()
                ->after('resi_last_printed_at');

            $table->unsignedInteger('resi_print_count')
                ->default(0)
                ->after('resi_printed_by');

            $table->foreign('resi_printed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropForeign(['resi_printed_by']);

            $table->dropColumn([
                'resi_printed_at',
                'resi_last_printed_at',
                'resi_printed_by',
                'resi_print_count',
            ]);
        });
    }
};

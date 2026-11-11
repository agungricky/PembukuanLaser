<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->decimal('cod', 15, 2)
                ->nullable()
                ->default(0)
                ->after('jenis_transaksi');

            $table->decimal('non_cod', 15, 2)
                ->nullable()
                ->default(0)
                ->after('cod');

            $table->decimal('dp', 15, 2)
                ->nullable()
                ->default(0)
                ->after('non_cod');

            $table->string('bukti_bayar')
                ->nullable()
                ->change();

            $table->dropColumn(['nominal', 'kekurangan']);
        });
    }

    public function down(): void
    {
        Schema::table('bukti_pembayarans', function (Blueprint $table) {
            $table->decimal('nominal', 15, 2)
                ->default(0)
                ->after('jenis_transaksi');

            $table->decimal('kekurangan', 15, 2)
                ->default(0)
                ->after('nominal');

            $table->string('bukti_bayar')
                ->nullable(false)
                ->change();

            $table->dropColumn(['cod', 'non_cod', 'dp']);
        });
    }
};

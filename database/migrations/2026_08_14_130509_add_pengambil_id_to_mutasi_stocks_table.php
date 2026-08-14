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
        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->unsignedBigInteger('pengambil_id')
                ->nullable()
                ->after('user_id');

            $table->foreign('pengambil_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_stoks', function (Blueprint $table) {
            $table->dropForeign(['pengambil_id']);
            $table->dropColumn('pengambil_id');
        });
    }
};

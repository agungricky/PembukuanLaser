
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->uuid('client_request_id')
                ->nullable()
                ->after('packing_user_id');

            $table->unique(
                ['packing_user_id', 'client_request_id'],
                'chat_packing_request_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('chat_klarifikasi_packings', function (Blueprint $table) {
            $table->dropUnique('chat_packing_request_unique');
            $table->dropColumn('client_request_id');
        });
    }
};

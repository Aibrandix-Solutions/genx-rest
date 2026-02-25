<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_register_session_id')
                ->nullable()
                ->after('received_by_user_id')
                ->comment('Links this hotel payment to the front-desk cash register session, enabling unified cash flow tracking');

            // Only add FK if the cash_register_sessions table exists
            if (Schema::hasTable('cash_register_sessions')) {
                $table->foreign('cash_register_session_id')
                      ->references('id')
                      ->on('cash_register_sessions')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotel_payments', function (Blueprint $table) {
            if (Schema::hasTable('cash_register_sessions')) {
                $table->dropForeign(['cash_register_session_id']);
            }
            $table->dropColumn('cash_register_session_id');
        });
    }
};

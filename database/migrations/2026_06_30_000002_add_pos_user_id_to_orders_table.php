<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'pos_user_id')) {
                $table->unsignedBigInteger('pos_user_id')->nullable()->after('waiter_id');
                $table->foreign('pos_user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pos_user_id')) {
                $table->dropForeign(['pos_user_id']);
                $table->dropColumn('pos_user_id');
            }
        });
    }
};

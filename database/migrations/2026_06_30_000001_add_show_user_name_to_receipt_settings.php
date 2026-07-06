<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receipt_settings')) {
            return;
        }

        Schema::table('receipt_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('receipt_settings', 'show_user_name')) {
                $table->boolean('show_user_name')->default(false)->after('show_waiter');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('receipt_settings')) {
            return;
        }

        Schema::table('receipt_settings', function (Blueprint $table) {
            if (Schema::hasColumn('receipt_settings', 'show_user_name')) {
                $table->dropColumn('show_user_name');
            }
        });
    }
};

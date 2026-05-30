<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'combo_instance_key')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('combo_instance_key', 191)->nullable()->after('combo_pack_id');
            });
        }

        if (Schema::hasTable('kot_items') && ! Schema::hasColumn('kot_items', 'combo_instance_key')) {
            Schema::table('kot_items', function (Blueprint $table) {
                $table->string('combo_instance_key', 191)->nullable()->after('combo_pack_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'combo_instance_key')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('combo_instance_key');
            });
        }

        if (Schema::hasTable('kot_items') && Schema::hasColumn('kot_items', 'combo_instance_key')) {
            Schema::table('kot_items', function (Blueprint $table) {
                $table->dropColumn('combo_instance_key');
            });
        }
    }
};

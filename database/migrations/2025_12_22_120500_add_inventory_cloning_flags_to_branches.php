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
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('is_inventory_items_clone')->default(false)->after('is_clone_kot_setting');
            $table->boolean('is_recipes_clone')->default(false)->after('is_inventory_items_clone');
            $table->boolean('is_payment_accounts_clone')->default(false)->after('is_recipes_clone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'is_inventory_items_clone',
                'is_recipes_clone',
                'is_payment_accounts_clone'
            ]);
        });
    }
};

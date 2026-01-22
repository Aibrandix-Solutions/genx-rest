<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_item_categories', function (Blueprint $table) {
            // Add restaurant_id column
            if (!Schema::hasColumn('inventory_item_categories', 'restaurant_id')) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            }
        });

        // Migrate existing data: assign all categories to restaurant 1
        DB::statement('UPDATE inventory_item_categories SET restaurant_id = 1 WHERE restaurant_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_item_categories', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });
    }
};

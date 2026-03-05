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
        // Step 1: Create many-to-many pivot table for menu items ↔ kitchen places
        if (!Schema::hasTable('menu_item_kot_place')) {
            Schema::create('menu_item_kot_place', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('menu_item_id');
                $table->unsignedBigInteger('kot_place_id');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');
                $table->foreign('kot_place_id')->references('id')->on('kot_places')->onDelete('cascade');
                $table->unique(['menu_item_id', 'kot_place_id']);
            });
        }

        // Step 2: Migrate existing kot_place_id data into pivot table
        $menuItems = DB::table('menu_items')->whereNotNull('kot_place_id')->get(['id', 'kot_place_id']);
        foreach ($menuItems as $item) {
            DB::table('menu_item_kot_place')->insertOrIgnore([
                'menu_item_id' => $item->id,
                'kot_place_id' => $item->kot_place_id,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 3: Add claim tracking columns to kot_items
        if (!Schema::hasColumn('kot_items', 'claimed_by_kitchen_id')) {
            Schema::table('kot_items', function (Blueprint $table) {
                $table->unsignedBigInteger('claimed_by_kitchen_id')->nullable()->after('status');
                $table->timestamp('claimed_at')->nullable()->after('claimed_by_kitchen_id');
                $table->boolean('is_multi_kitchen')->default(false)->after('claimed_at');

                $table->foreign('claimed_by_kitchen_id')->references('id')->on('kot_places')->nullOnDelete();
            });
        }

        // Step 4: Add order_type columns to kot_items if not already present
        if (!Schema::hasColumn('kot_items', 'order_type_id')) {
            Schema::table('kot_items', function (Blueprint $table) {
                $table->unsignedBigInteger('order_type_id')->nullable()->after('menu_item_variation_id');
                $table->string('order_type')->nullable()->after('order_type_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove claim columns from kot_items
        Schema::table('kot_items', function (Blueprint $table) {
            $table->dropForeign(['claimed_by_kitchen_id']);
            $table->dropColumn(['claimed_by_kitchen_id', 'claimed_at', 'is_multi_kitchen']);
        });

        Schema::dropIfExists('menu_item_kot_place');
    }
};

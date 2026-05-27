<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an `item_code` column to inventory_items, a regular index for fast
 * lookups, and a composite unique index on (restaurant_id, item_code) so
 * codes are unique per restaurant. NULL values are allowed and are not
 * treated as duplicates by MySQL unique indexes, which lets us auto-fill
 * for existing rows lazily without breaking inserts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_items', 'item_code')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->string('item_code', 50)->nullable()->after('name');
                $table->index('item_code', 'inventory_items_item_code_index');
            });
        }

        // Composite unique index on (restaurant_id, item_code).
        $hasUnique = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_restaurant_item_code_unique'");
        if (empty($hasUnique)) {
            // De-duplicate any existing rows that would violate the constraint
            // (only relevant if item_code was somehow populated by another path).
            DB::statement("
                UPDATE inventory_items i
                INNER JOIN (
                    SELECT MIN(id) AS keep_id, restaurant_id, item_code
                    FROM inventory_items
                    WHERE item_code IS NOT NULL AND item_code != ''
                    GROUP BY restaurant_id, item_code
                    HAVING COUNT(*) > 1
                ) dupes
                    ON  i.restaurant_id = dupes.restaurant_id
                    AND i.item_code     = dupes.item_code
                    AND i.id           != dupes.keep_id
                SET i.item_code = NULL
            ");

            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'item_code'], 'inventory_items_restaurant_item_code_unique');
            });
        }
    }

    public function down(): void
    {
        $hasUnique = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_restaurant_item_code_unique'");
        if (!empty($hasUnique)) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropUnique('inventory_items_restaurant_item_code_unique');
            });
        }

        if (Schema::hasColumn('inventory_items', 'item_code')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $hasIndex = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_item_code_index'");
                if (!empty($hasIndex)) {
                    $table->dropIndex('inventory_items_item_code_index');
                }
                $table->dropColumn('item_code');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1) Add restaurant_id column to inventory_items (nullable first for safe backfill)
        if (!Schema::hasColumn('inventory_items', 'restaurant_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $table->index('restaurant_id');
            });
        }

        // 2) Backfill item.restaurant_id (best-effort, in priority order)
        if (Schema::hasColumn('inventory_items', 'branch_id')) {
            DB::statement(<<<'SQL'
UPDATE inventory_items i
INNER JOIN branches b ON b.id = i.branch_id
SET i.restaurant_id = b.restaurant_id
WHERE i.restaurant_id IS NULL
SQL
            );
        }

        if (Schema::hasColumn('inventory_items', 'inventory_item_category_id') && Schema::hasColumn('inventory_item_categories', 'restaurant_id')) {
            DB::statement(<<<'SQL'
UPDATE inventory_items i
INNER JOIN inventory_item_categories c ON c.id = i.inventory_item_category_id
SET i.restaurant_id = c.restaurant_id
WHERE i.restaurant_id IS NULL
  AND c.restaurant_id IS NOT NULL
SQL
            );
        }

        if (Schema::hasTable('inventory_stocks') && Schema::hasColumn('inventory_stocks', 'branch_id')) {
            DB::statement(<<<'SQL'
UPDATE inventory_items i
INNER JOIN (
    SELECT s.inventory_item_id, MIN(b.restaurant_id) AS restaurant_id
    FROM inventory_stocks s
    INNER JOIN branches b ON b.id = s.branch_id
    GROUP BY s.inventory_item_id
) x ON x.inventory_item_id = i.id
SET i.restaurant_id = x.restaurant_id
WHERE i.restaurant_id IS NULL
SQL
            );
        }

        if (Schema::hasTable('purchase_order_items') && Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'branch_id')) {
            DB::statement(<<<'SQL'
UPDATE inventory_items i
INNER JOIN (
    SELECT poi.inventory_item_id, MIN(b.restaurant_id) AS restaurant_id
    FROM purchase_order_items poi
    INNER JOIN purchase_orders po ON po.id = poi.purchase_order_id
    INNER JOIN branches b ON b.id = po.branch_id
    GROUP BY poi.inventory_item_id
) x ON x.inventory_item_id = i.id
SET i.restaurant_id = x.restaurant_id
WHERE i.restaurant_id IS NULL
SQL
            );
        }

        if (Schema::hasColumn('inventory_items', 'preferred_supplier_id') && Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'restaurant_id')) {
            DB::statement(<<<'SQL'
UPDATE inventory_items i
INNER JOIN suppliers s ON s.id = i.preferred_supplier_id
SET i.restaurant_id = s.restaurant_id
WHERE i.restaurant_id IS NULL
  AND i.preferred_supplier_id IS NOT NULL
SQL
            );
        }

        // 3) If single restaurant install, default remaining NULLs
        $restaurantCount = (int) DB::table('restaurants')->count();
        if ($restaurantCount === 1) {
            $restaurantId = (int) DB::table('restaurants')->min('id');

            DB::table('inventory_items')->whereNull('restaurant_id')->update(['restaurant_id' => $restaurantId]);
        }

        // 4) Add foreign key on inventory_items.restaurant_id (after backfill)
        if (Schema::hasColumn('inventory_items', 'restaurant_id')) {
                        $existing = DB::select(<<<'SQL'
SELECT CONSTRAINT_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inventory_items'
    AND COLUMN_NAME = 'restaurant_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
SQL
                        );

            if (empty($existing)) {
                Schema::table('inventory_items', function (Blueprint $table) {
                    $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
                });
            }
        }

        // 5) Fix uniqueness: name must be unique per restaurant, not globally
        $hasLegacyUnique = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_name_unique'");
        if (!empty($hasLegacyUnique)) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropUnique('inventory_items_name_unique');
            });
        }

        $hasCompositeUnique = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_restaurant_name_unique'");
        if (empty($hasCompositeUnique) && Schema::hasColumn('inventory_items', 'restaurant_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'name'], 'inventory_items_restaurant_name_unique');
            });
        }

        // Warn if we couldn't backfill in a multi-restaurant DB
        if ($restaurantCount > 1) {
            $nullItemCount = (int) DB::table('inventory_items')->whereNull('restaurant_id')->count();
            if ($nullItemCount > 0) {
                \Log::warning('Inventory restaurant scoping migration left NULL restaurant_id values', [
                    'null_inventory_items' => $nullItemCount,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop composite unique if present
        $hasCompositeUnique = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_restaurant_name_unique'");
        if (!empty($hasCompositeUnique)) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropUnique('inventory_items_restaurant_name_unique');
            });
        }

        // Restore legacy unique on name (best-effort)
        $hasLegacyUnique = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_name_unique'");
        if (empty($hasLegacyUnique)) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unique('name', 'inventory_items_name_unique');
            });
        }

        if (Schema::hasColumn('inventory_items', 'restaurant_id')) {
            // Drop FK if exists
            $foreignKeys = DB::select(<<<'SQL'
SELECT CONSTRAINT_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'inventory_items'
  AND COLUMN_NAME = 'restaurant_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL
SQL
            );
            if (!empty($foreignKeys)) {
                Schema::table('inventory_items', function (Blueprint $table) use ($foreignKeys) {
                    foreach ($foreignKeys as $fk) {
                        $table->dropForeign($fk->CONSTRAINT_NAME);
                    }
                });
            }

            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropIndex(['restaurant_id']);
                $table->dropColumn('restaurant_id');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};

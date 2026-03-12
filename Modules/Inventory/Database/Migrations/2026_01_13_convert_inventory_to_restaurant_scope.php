<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Entities\InventoryItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration consolidates duplicate inventory items across branches
     * and converts from branch-scoped to restaurant-scoped architecture.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Step 1: Identify and consolidate duplicate items
        $this->consolidateDuplicateItems();

        // Step 2: Remove branch_id column from inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'branch_id')) {
                // Drop foreign key if exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'inventory_items' 
                    AND COLUMN_NAME = 'branch_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
                
                $table->dropColumn('branch_id');
            }
        });

        // Step 3: Add unique constraint on name (globally unique for single-restaurant systems)
        Schema::table('inventory_items', function (Blueprint $table) {
            // Check if unique constraint already exists
            $indexes = DB::select("SHOW INDEXES FROM inventory_items WHERE Key_name = 'inventory_items_name_unique'");
            
            if (empty($indexes)) {
                $table->unique('name', 'inventory_items_name_unique');
            }
        });

        // Step 4: Also make inventory_item_categories restaurant-scoped (optional but recommended)
        if (Schema::hasColumn('inventory_item_categories', 'branch_id')) {
            // First consolidate category duplicates
            $this->consolidateDuplicateCategories();
            
            Schema::table('inventory_item_categories', function (Blueprint $table) {
                // Drop foreign key if exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'inventory_item_categories' 
                    AND COLUMN_NAME = 'branch_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
                
                $table->dropColumn('branch_id');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Consolidate duplicate inventory items
     */
    private function consolidateDuplicateItems(): void
    {
        // Find duplicate sets (same name across different branches)
        $duplicateSets = DB::select("SELECT name, COUNT(*) as item_count FROM inventory_items GROUP BY name HAVING COUNT(*) > 1");

        foreach ($duplicateSets as $set) {
            // Get all items with this name, ordered by creation date
            $items = DB::table('inventory_items')
                ->where('name', $set->name)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($items->count() <= 1) {
                continue;
            }

            // Keep the first (oldest) item as master
            $masterItemId = $items->first()->id;
            $duplicateIds = $items->skip(1)->pluck('id')->toArray();

            if (empty($duplicateIds)) {
                continue;
            }

            // Migrate all references from duplicates to master
            
            // 1. Update inventory_stocks
            DB::table('inventory_stocks')
                ->whereIn('inventory_item_id', $duplicateIds)
                ->update(['inventory_item_id' => $masterItemId]);

            // 2. Update inventory_movements
            DB::table('inventory_movements')
                ->whereIn('inventory_item_id', $duplicateIds)
                ->update(['inventory_item_id' => $masterItemId]);

            // 3. Update recipes
            if (Schema::hasTable('recipes')) {
                DB::table('recipes')
                    ->whereIn('inventory_item_id', $duplicateIds)
                    ->update(['inventory_item_id' => $masterItemId]);
            }

            // 4. Update inventory_transfer_items (source)
            DB::table('inventory_transfer_items')
                ->whereIn('source_inventory_item_id', $duplicateIds)
                ->update(['source_inventory_item_id' => $masterItemId]);

            // 5. Update inventory_transfer_items (destination)
            DB::table('inventory_transfer_items')
                ->whereIn('destination_inventory_item_id', $duplicateIds)
                ->update(['destination_inventory_item_id' => $masterItemId]);

            // 6. Delete duplicate items
            DB::table('inventory_items')
                ->whereIn('id', $duplicateIds)
                ->delete();

            // Log consolidation
            \Log::info("Consolidated inventory item: {$set->name}", [
                'master_id' => $masterItemId,
                'merged_ids' => $duplicateIds,
                'count' => count($duplicateIds) + 1
            ]);
        }
    }

    /**
     * Consolidate duplicate categories
     */
    private function consolidateDuplicateCategories(): void
    {
        // Find duplicate category sets
        $duplicateSets = DB::select("
            SELECT name, COUNT(*) as cat_count
            FROM inventory_item_categories
            GROUP BY name
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicateSets as $set) {
            // Get all categories with this name
            $categories = DB::table('inventory_item_categories')
                ->where('name', $set->name)
                ->orderBy('id', 'asc')
                ->get();

            if ($categories->count() <= 1) {
                continue;
            }

            // Keep the first category as master
            $masterCategoryId = $categories->first()->id;
            $duplicateIds = $categories->skip(1)->pluck('id')->toArray();

            if (empty($duplicateIds)) {
                continue;
            }

            // Update items using duplicate categories
            DB::table('inventory_items')
                ->whereIn('inventory_item_category_id', $duplicateIds)
                ->update(['inventory_item_category_id' => $masterCategoryId]);

            // Delete duplicate categories
            DB::table('inventory_item_categories')
                ->whereIn('id', $duplicateIds)
                ->delete();

            \Log::info("Consolidated category: {$set->name}", [
                'master_id' => $masterCategoryId,
                'merged_ids' => $duplicateIds
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // WARNING: This rollback cannot restore the original branch-scoped structure
        // You must restore from database backup to fully roll back
        
        Schema::table('inventory_items', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('inventory_items_name_unique');
            
            // Note: branch_id column still exists, we just removed scoping
        });

        if (!Schema::hasColumn('inventory_item_categories', 'branch_id')) {
            Schema::table('inventory_item_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }
    }
};

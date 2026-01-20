<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration consolidates duplicate units across branches
     * and converts from branch-scoped to restaurant-scoped architecture.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Step 1: Consolidate duplicate units
        $this->consolidateDuplicateUnits();

        // Step 2: Remove branch_id column from units
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'branch_id')) {
                // Drop foreign key if exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'units' 
                    AND COLUMN_NAME = 'branch_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
                
                $table->dropColumn('branch_id');
            }
        });

        // Step 3: Add unique constraint on name and symbol
        Schema::table('units', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEXES FROM units WHERE Key_name = 'units_name_symbol_unique'");
            
            if (empty($indexes)) {
                $table->unique(['name', 'symbol'], 'units_name_symbol_unique');
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Consolidate duplicate units
     */
    private function consolidateDuplicateUnits(): void
    {
        // Find duplicate unit sets (same name+symbol across branches)
        $duplicateSets = DB::select("
            SELECT name, symbol, COUNT(*) as unit_count
            FROM units
            GROUP BY name, symbol
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicateSets as $set) {
            // Get all units with this name and symbol
            $units = DB::table('units')
                ->where('name', $set->name)
                ->where('symbol', $set->symbol)
                ->orderBy('id', 'asc')
                ->get();

            if ($units->count() <= 1) {
                continue;
            }

            // Keep the first unit as master
            $masterUnitId = $units->first()->id;
            $duplicateIds = $units->skip(1)->pluck('id')->toArray();

            if (empty($duplicateIds)) {
                continue;
            }

            // Update references from duplicates to master

            // 1. Update inventory_items
            DB::table('inventory_items')
                ->whereIn('unit_id', $duplicateIds)
                ->update(['unit_id' => $masterUnitId]);

            // 2. Update recipes
            if (Schema::hasTable('recipes')) {
                DB::table('recipes')
                    ->whereIn('unit_id', $duplicateIds)
                    ->update(['unit_id' => $masterUnitId]);
            }

            // 3. Update inventory_transfer_items (if column exists)
            if (Schema::hasTable('inventory_transfer_items') && Schema::hasColumn('inventory_transfer_items', 'unit_id')) {
                DB::table('inventory_transfer_items')
                    ->whereIn('unit_id', $duplicateIds)
                    ->update(['unit_id' => $masterUnitId]);
            }

            // 4. Delete duplicate units
            DB::table('units')
                ->whereIn('id', $duplicateIds)
                ->delete();

            \Log::info("Consolidated unit: {$set->name} ({$set->symbol})", [
                'master_id' => $masterUnitId,
                'merged_ids' => $duplicateIds,
                'count' => count($duplicateIds) + 1
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
        
        Schema::table('units', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('units_name_symbol_unique');
        });

        if (!Schema::hasColumn('units', 'branch_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }
    }
};

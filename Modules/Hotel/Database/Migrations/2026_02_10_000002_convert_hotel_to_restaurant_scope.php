<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Convert all Hotel module tables from branch-scoped (branch_id) to restaurant-scoped (restaurant_id).
 * Idempotent — safe to run even if partially applied.
 */
return new class extends Migration
{
    public function up()
    {
        $restaurantId = DB::table('restaurants')->value('id') ?? 1;

        $this->convertTable('hotel_room_types', $restaurantId, 'simple');
        $this->convertTable('hotel_rooms', $restaurantId, 'compound');
        $this->convertTable('hotel_guests', $restaurantId, 'simple_no_fk');
        $this->convertTable('hotel_reservations', $restaurantId, 'compound');
        $this->convertTable('hotel_housekeeping_tasks', $restaurantId, 'compound');
        $this->convertTable('hotel_payments', $restaurantId, 'simple');

        // hotel_settings needs unique constraint instead of simple index
        if (!Schema::hasColumn('hotel_settings', 'restaurant_id')) {
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            });
            DB::table('hotel_settings')->update(['restaurant_id' => $restaurantId]);
        }
        // Deduplicate: keep one settings row per restaurant
        $dupSettings = DB::table('hotel_settings')
            ->select('restaurant_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('restaurant_id')
            ->get();
        foreach ($dupSettings as $row) {
            DB::table('hotel_settings')
                ->where('restaurant_id', $row->restaurant_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }
        if (Schema::hasColumn('hotel_settings', 'branch_id')) {
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropUnique(['branch_id']);
                $table->dropColumn('branch_id');
            });
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->unique('restaurant_id');
            });
        }
    }

    /**
     * @param string $table
     * @param int $restaurantId
     * @param string $mode  'simple' | 'compound' | 'simple_no_fk'
     */
    private function convertTable(string $table, int $restaurantId, string $mode): void
    {
        // Step 1: Add restaurant_id if not present
        if (!Schema::hasColumn($table, 'restaurant_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $t->index('restaurant_id');
            });
            DB::table($table)->update(['restaurant_id' => $restaurantId]);
        }

        // Step 2: Remove branch_id if still present
        if (Schema::hasColumn($table, 'branch_id')) {
            Schema::table($table, function (Blueprint $t) use ($mode) {
                if ($mode !== 'simple_no_fk') {
                    $t->dropForeign(['branch_id']);
                }
                if ($mode === 'compound') {
                    $t->dropIndex(['branch_id', 'status']);
                } else {
                    $t->dropIndex(['branch_id']);
                }
                $t->dropColumn('branch_id');
            });

            // Add compound index for restaurant_id + status if applicable
            if ($mode === 'compound') {
                Schema::table($table, function (Blueprint $t) {
                    $t->index(['restaurant_id', 'status']);
                });
            }
        }
    }

    public function down()
    {
        // One-way conversion — not reversible.
    }
};

<?php

use Illuminate\Support\Facades\DB;
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
        // Single-restaurant deployments only: assigns one restaurant_id to all legacy branch rows.
        // Multi-tenant installs must map branch_id → restaurant_id per row before running this.
        $restaurantId = (int) (DB::table('restaurants')->orderBy('id')->value('id') ?? 1);

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
            $this->dropForeignIfExists('hotel_settings', 'branch_id');
            Schema::table('hotel_settings', function (Blueprint $table) {
                if (Schema::hasColumn('hotel_settings', 'branch_id')) {
                    try {
                        $table->dropUnique(['branch_id']);
                    } catch (\Throwable) {
                        // Unique may already be removed.
                    }
                    $table->dropColumn('branch_id');
                }
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
            if ($mode !== 'simple_no_fk') {
                $this->dropForeignIfExists($table, 'branch_id');
            }
            if ($mode === 'compound') {
                $this->dropIndexIfExists($table, ['branch_id', 'status']);
            } else {
                $this->dropIndexIfExists($table, ['branch_id']);
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('branch_id');
            });

            // Add compound index for restaurant_id + status if applicable
            if ($mode === 'compound' && !$this->indexExists($table, ['restaurant_id', 'status'])) {
                Schema::table($table, function (Blueprint $t) {
                    $t->index(['restaurant_id', 'status']);
                });
            }
        }
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->dropForeign([$column]);
            });
        } catch (\Throwable) {
            // FK may already be removed on a partially migrated database.
        }
    }

    private function dropIndexIfExists(string $table, array $columns): void
    {
        if ($this->indexExists($table, $columns)) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                $t->dropIndex($columns);
            });
        }
    }

    private function indexExists(string $table, array $columns): bool
    {
        $database = DB::getDatabaseName();
        $indexName = implode('_', array_merge([$table], $columns, ['index']));

        $match = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, $table, $indexName]
        );

        if (!empty($match)) {
            return true;
        }

        // Laravel may name compound indexes differently; match by column set.
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $params = array_merge([$database, $table], $columns, [count($columns)]);

        $rows = DB::select(
            "SELECT index_name, COUNT(*) as col_count
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ?
               AND column_name IN ({$placeholders})
             GROUP BY index_name
             HAVING col_count = ?",
            $params
        );

        return !empty($rows);
    }

    public function down()
    {
        // One-way conversion — not reversible.
    }
};

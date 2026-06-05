<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Complete branch_id → restaurant_id conversion when 2026_02_10_000002 partially applied
 * (restaurant_id added but branch_id still NOT NULL, blocking inserts).
 */
return new class extends Migration
{
    public function up(): void
    {
        $fallbackRestaurantId = DB::table('restaurants')->count() === 1
            ? (int) (DB::table('restaurants')->value('id') ?? 0)
            : 0;

        $this->finishTable('hotel_settings', $fallbackRestaurantId, 'settings');
        $this->finishTable('hotel_room_types', $fallbackRestaurantId, 'simple');
        $this->finishTable('hotel_rooms', $fallbackRestaurantId, 'compound_unique_room');
        $this->finishTable('hotel_guests', $fallbackRestaurantId, 'simple_no_fk');
        $this->finishTable('hotel_reservations', $fallbackRestaurantId, 'compound');
        $this->finishTable('hotel_housekeeping_tasks', $fallbackRestaurantId, 'compound');
        $this->finishTable('hotel_payments', $fallbackRestaurantId, 'simple');
    }

    private function finishTable(string $table, int $fallbackRestaurantId, string $mode): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        if (!Schema::hasColumn($table, 'restaurant_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $t->index('restaurant_id');
            });
        }

        if (Schema::hasTable('branches')) {
            DB::statement("
                UPDATE {$table} t
                INNER JOIN branches b ON b.id = t.branch_id
                SET t.restaurant_id = b.restaurant_id
                WHERE t.restaurant_id IS NULL AND t.branch_id IS NOT NULL
            ");
        }

        if ($fallbackRestaurantId > 0) {
            DB::table($table)->whereNull('restaurant_id')->update(['restaurant_id' => $fallbackRestaurantId]);
        }

        if ($mode !== 'simple_no_fk') {
            $this->dropForeignIfExists($table, 'branch_id');
        }

        $this->dropIndexesReferencingColumn($table, 'branch_id');

        try {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'branch_id')) {
                    $t->dropColumn('branch_id');
                }
            });
        } catch (\Throwable) {
            // Column may already be removed.
        }

        if ($mode === 'compound' && !$this->indexExists($table, ['restaurant_id', 'status'])) {
            Schema::table($table, function (Blueprint $t) {
                $t->index(['restaurant_id', 'status']);
            });
        }

        if ($mode === 'compound_unique_room' && !$this->indexExists($table, ['restaurant_id', 'room_number'])) {
            Schema::table($table, function (Blueprint $t) {
                $t->unique(['restaurant_id', 'room_number']);
            });
        }

        if ($mode === 'settings') {
            $this->ensureRestaurantIdUnique($table);
        }
    }

    private function ensureRestaurantIdUnique(string $table): void
    {
        if (!Schema::hasColumn($table, 'restaurant_id')) {
            return;
        }

        $database = DB::getDatabaseName();
        $hasUnique = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND column_name = ? AND non_unique = 0
             LIMIT 1',
            [$database, $table, 'restaurant_id']
        );

        if (!empty($hasUnique)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) {
            $t->unique('restaurant_id');
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->dropForeign([$column]);
            });
        } catch (\Throwable) {
            // FK may already be removed.
        }
    }

    private function dropIndexesReferencingColumn(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $database = DB::getDatabaseName();
        $indexes = DB::select(
            'SELECT DISTINCT index_name
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND column_name = ?
               AND index_name != ?',
            [$database, $table, $column, 'PRIMARY']
        );

        foreach ($indexes as $index) {
            try {
                Schema::table($table, function (Blueprint $t) use ($index) {
                    $t->dropIndex($index->index_name);
                });
            } catch (\Throwable) {
                // Index may already be dropped.
            }
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

    public function down(): void
    {
        // One-way schema fix.
    }
};

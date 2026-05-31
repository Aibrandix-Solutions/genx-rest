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
        // Fallback restaurant_id only when the database has a single tenant (see backfillNullRestaurantId).
        $fallbackRestaurantId = $this->singleRestaurantFallbackId();

        // Convert settings first — dashboard and sidebar query this table early.
        $this->convertHotelSettings($fallbackRestaurantId);

        $this->convertTable('hotel_room_types', $fallbackRestaurantId, 'simple');
        $this->convertTable('hotel_rooms', $fallbackRestaurantId, 'compound');
        $this->convertTable('hotel_guests', $fallbackRestaurantId, 'simple_no_fk');
        $this->convertTable('hotel_reservations', $fallbackRestaurantId, 'compound');
        $this->convertTable('hotel_housekeeping_tasks', $fallbackRestaurantId, 'compound');
        $this->convertTable('hotel_payments', $fallbackRestaurantId, 'simple');
    }

    /**
     * Only used to back-fill rows with no branch_id on single-restaurant databases.
     */
    private function singleRestaurantFallbackId(): int
    {
        if (DB::table('restaurants')->count() !== 1) {
            return 0;
        }

        return (int) (DB::table('restaurants')->value('id') ?? 0);
    }

    private function backfillRestaurantIdFromBranch(string $table): void
    {
        if (!Schema::hasTable($table)
            || !Schema::hasColumn($table, 'branch_id')
            || !Schema::hasColumn($table, 'restaurant_id')
            || !Schema::hasTable('branches')) {
            return;
        }

        DB::statement("
            UPDATE {$table} t
            INNER JOIN branches b ON b.id = t.branch_id
            SET t.restaurant_id = b.restaurant_id
            WHERE t.restaurant_id IS NULL AND t.branch_id IS NOT NULL
        ");
    }

    private function backfillNullRestaurantId(string $table, int $fallbackRestaurantId): void
    {
        if ($fallbackRestaurantId <= 0 || !Schema::hasTable($table) || !Schema::hasColumn($table, 'restaurant_id')) {
            return;
        }

        DB::table($table)
            ->whereNull('restaurant_id')
            ->update(['restaurant_id' => $fallbackRestaurantId]);
    }

    private function convertHotelSettings(int $fallbackRestaurantId): void
    {
        if (!Schema::hasTable('hotel_settings')) {
            return;
        }

        if (!Schema::hasColumn('hotel_settings', 'restaurant_id')) {
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            });
        }

        $this->backfillRestaurantIdFromBranch('hotel_settings');
        $this->backfillNullRestaurantId('hotel_settings', $fallbackRestaurantId);

        $dupSettings = DB::table('hotel_settings')
            ->whereNotNull('restaurant_id')
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
            $this->dropIndexesReferencingColumn('hotel_settings', 'branch_id');

            try {
                Schema::table('hotel_settings', function (Blueprint $table) {
                    if (Schema::hasColumn('hotel_settings', 'branch_id')) {
                        $table->dropColumn('branch_id');
                    }
                });
            } catch (\Throwable) {
                // Column may already be removed on a partially migrated database.
            }
        }

        $this->ensureHotelSettingsRestaurantIdUnique();
    }

    private function ensureHotelSettingsRestaurantIdUnique(): void
    {
        if (!Schema::hasTable('hotel_settings') || !Schema::hasColumn('hotel_settings', 'restaurant_id')) {
            return;
        }

        $database = DB::getDatabaseName();
        $hasUnique = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND column_name = ? AND non_unique = 0
             LIMIT 1',
            [$database, 'hotel_settings', 'restaurant_id']
        );

        if (!empty($hasUnique)) {
            return;
        }

        Schema::table('hotel_settings', function (Blueprint $table) {
            $table->unique('restaurant_id');
        });
    }

    /**
     * @param string $table
     * @param int $fallbackRestaurantId Only applied when restaurants.count() === 1
     * @param string $mode  'simple' | 'compound' | 'simple_no_fk'
     */
    private function convertTable(string $table, int $fallbackRestaurantId, string $mode): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // Step 1: Add restaurant_id and map branch_id → branches.restaurant_id per row
        if (!Schema::hasColumn($table, 'restaurant_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $t->index('restaurant_id');
            });
        }

        $this->backfillRestaurantIdFromBranch($table);
        $this->backfillNullRestaurantId($table, $fallbackRestaurantId);

        // Step 2: Remove branch_id if still present
        if (Schema::hasColumn($table, 'branch_id')) {
            if ($mode !== 'simple_no_fk') {
                $this->dropForeignIfExists($table, 'branch_id');
            }

            // Drop every index/unique that references branch_id (e.g. hotel_rooms unique on branch_id+room_number).
            $this->dropIndexesReferencingColumn($table, 'branch_id');

            try {
                Schema::table($table, function (Blueprint $t) {
                    if (Schema::hasColumn($table, 'branch_id')) {
                        $t->dropColumn('branch_id');
                    }
                });
            } catch (\Throwable) {
                // Column may already be removed on a partially migrated database.
            }

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

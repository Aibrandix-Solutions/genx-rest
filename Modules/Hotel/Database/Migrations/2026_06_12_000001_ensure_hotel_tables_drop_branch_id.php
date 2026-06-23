<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Drop legacy branch_id from hotel tables after restaurant-scoped conversion.
 * Idempotent — fixes DBs where convert_hotel_to_restaurant_scope did not fully apply.
 */
return new class extends Migration
{
    private const TABLES = [
        'hotel_room_types',
        'hotel_rooms',
        'hotel_guests',
        'hotel_reservations',
        'hotel_housekeeping_tasks',
        'hotel_payments',
    ];

    public function up(): void
    {
        $fallbackRestaurantId = DB::table('restaurants')->count() === 1
            ? (int) (DB::table('restaurants')->value('id') ?? 0)
            : 0;

        foreach (self::TABLES as $table) {
            $this->ensureRestaurantScoped($table, $fallbackRestaurantId);
        }

        $this->ensureHotelRoomsRestaurantIndexes();
        $this->ensureHotelReservationsRestaurantIndex();
    }

    private function ensureRestaurantScoped(string $table, int $fallbackRestaurantId): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, 'restaurant_id')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $blueprint->index('restaurant_id');
            });
        }

        if (Schema::hasColumn($table, 'branch_id') && Schema::hasTable('branches')) {
            DB::statement("
                UPDATE {$table} t
                INNER JOIN branches b ON b.id = t.branch_id
                SET t.restaurant_id = b.restaurant_id
                WHERE t.restaurant_id IS NULL AND t.branch_id IS NOT NULL
            ");
        }

        if ($fallbackRestaurantId > 0) {
            DB::table($table)
                ->whereNull('restaurant_id')
                ->update(['restaurant_id' => $fallbackRestaurantId]);
        }

        if (!Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        $this->dropForeignIfExists($table, 'branch_id');
        $this->dropIndexesReferencingColumn($table, 'branch_id');

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (Schema::hasColumn($table, 'branch_id')) {
                $blueprint->dropColumn('branch_id');
            }
        });
    }

    private function ensureHotelRoomsRestaurantIndexes(): void
    {
        if (!Schema::hasTable('hotel_rooms') || !Schema::hasColumn('hotel_rooms', 'restaurant_id')) {
            return;
        }

        if (!$this->indexExists('hotel_rooms', ['restaurant_id', 'room_number'])) {
            Schema::table('hotel_rooms', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'room_number']);
            });
        }

        if (Schema::hasColumn('hotel_rooms', 'status')
            && !$this->indexExists('hotel_rooms', ['restaurant_id', 'status'])) {
            Schema::table('hotel_rooms', function (Blueprint $table) {
                $table->index(['restaurant_id', 'status']);
            });
        }
    }

    private function ensureHotelReservationsRestaurantIndex(): void
    {
        if (!Schema::hasTable('hotel_reservations')
            || !Schema::hasColumn('hotel_reservations', 'restaurant_id')
            || !Schema::hasColumn('hotel_reservations', 'status')) {
            return;
        }

        if (!$this->indexExists('hotel_reservations', ['restaurant_id', 'status'])) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->index(['restaurant_id', 'status']);
            });
        }
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
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
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropIndex($index->index_name);
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
        // One-way data fix.
    }
};

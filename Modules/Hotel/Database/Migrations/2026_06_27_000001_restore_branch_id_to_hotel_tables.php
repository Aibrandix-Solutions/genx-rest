<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Restore branch-level isolation to all hotel tables.
 *
 * History: branch_id was originally present (2026_02_05), then merged into
 * restaurant_id scope (2026_02_10 through 2026_06_12). This migration reverses
 * that, making each branch have its own independent hotel property — matching how
 * Orders, Tables, and Menu Items already work.
 *
 * Backfill: existing hotel rows are assigned to the first branch (lowest id) of
 * their restaurant. Other branches start empty and are configured manually.
 * restaurant_id is kept for cross-branch reports and backward compatibility.
 *
 * Idempotent — safe to re-run if it was partially applied.
 */
return new class extends Migration
{
    /**
     * Tables that have restaurant_id and just need a straightforward backfill.
     * hotel_room_charges is handled separately (no restaurant_id column).
     */
    private array $restaurantScopedTables = [
        'hotel_settings',
        'hotel_room_types',
        'hotel_rooms',
        'hotel_guests',
        'hotel_reservations',
        'hotel_housekeeping_tasks',
        'hotel_payments',
        'hotel_expenses',
        'hotel_room_prices',
    ];

    public function up(): void
    {
        // Step 1: Add branch_id column where missing
        $this->addBranchIdColumns();

        // Step 2: Backfill branch_id from restaurant → first branch mapping
        $this->backfillRestaurantScopedTables();

        // Step 3: Backfill hotel_room_charges through reservation FK
        $this->backfillRoomCharges();

        // Step 4: Apply NOT NULL + FK + unique constraints
        $this->applyConstraints();
    }

    public function down(): void
    {
        $allTables = array_merge($this->restaurantScopedTables, ['hotel_room_charges']);

        foreach ($allTables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $this->safeDropForeign($t, $table, 'branch_id');
                $t->dropColumn('branch_id');
            });
        }

        // Restore old restaurant_id unique on hotel_settings
        if (Schema::hasTable('hotel_settings') && !$this->uniqueExists('hotel_settings', ['restaurant_id'])) {
            Schema::table('hotel_settings', function (Blueprint $t) {
                $t->unique('restaurant_id');
            });
        }

        // Restore global reservation_number unique
        if (Schema::hasTable('hotel_reservations') && !$this->uniqueExists('hotel_reservations', ['reservation_number'])) {
            Schema::table('hotel_reservations', function (Blueprint $t) {
                $t->unique('reservation_number');
            });
        }

        // Restore hotel_rooms unique on (restaurant_id, room_number)
        if (Schema::hasTable('hotel_rooms') && !$this->uniqueExists('hotel_rooms', ['restaurant_id', 'room_number'])) {
            Schema::table('hotel_rooms', function (Blueprint $t) {
                $t->unique(['restaurant_id', 'room_number']);
            });
        }
    }

    // -----------------------------------------------------------------------
    // Step 1 — add branch_id columns (skip if already present)
    // -----------------------------------------------------------------------

    private function addBranchIdColumns(): void
    {
        $allTables = array_merge($this->restaurantScopedTables, ['hotel_room_charges']);

        foreach ($allTables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('branch_id')->nullable()->after('id');
                $t->index('branch_id', 'tmp_branch_idx_' . $t->getTable());
            });
        }
    }

    // -----------------------------------------------------------------------
    // Step 2 — backfill tables that have restaurant_id
    // -----------------------------------------------------------------------

    private function backfillRestaurantScopedTables(): void
    {
        if (!Schema::hasTable('branches')) {
            return;
        }

        foreach ($this->restaurantScopedTables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            // Only update rows that still have NULL branch_id
            if (DB::table($table)->whereNull('branch_id')->doesntExist()) {
                continue;
            }

            DB::statement("
                UPDATE `{$table}` t
                INNER JOIN (
                    SELECT restaurant_id, MIN(id) AS first_branch_id
                    FROM branches
                    GROUP BY restaurant_id
                ) b ON b.restaurant_id = t.restaurant_id
                SET t.branch_id = b.first_branch_id
                WHERE t.branch_id IS NULL
            ");
        }
    }

    // -----------------------------------------------------------------------
    // Step 3 — backfill hotel_room_charges (no restaurant_id; use reservation FK)
    // -----------------------------------------------------------------------

    private function backfillRoomCharges(): void
    {
        if (!Schema::hasTable('hotel_room_charges') || !Schema::hasColumn('hotel_room_charges', 'branch_id')) {
            return;
        }

        if (DB::table('hotel_room_charges')->whereNull('branch_id')->doesntExist()) {
            return;
        }

        if (!Schema::hasTable('hotel_reservations') || !Schema::hasColumn('hotel_reservations', 'branch_id')) {
            return;
        }

        DB::statement("
            UPDATE hotel_room_charges rc
            INNER JOIN hotel_reservations r ON r.id = rc.reservation_id
            SET rc.branch_id = r.branch_id
            WHERE rc.branch_id IS NULL
              AND r.branch_id IS NOT NULL
        ");
    }

    // -----------------------------------------------------------------------
    // Step 4 — constraints
    // -----------------------------------------------------------------------

    private function applyConstraints(): void
    {
        $this->constrainHotelSettings();
        $this->constrainHotelRooms();
        $this->constrainHotelReservations();
        $this->constrainSimpleTables();
        $this->constrainRoomCharges();
    }

    private function constrainHotelSettings(): void
    {
        $table = 'hotel_settings';
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            // Drop old restaurant_id unique (one per restaurant → one per branch)
            if ($this->uniqueExists($table, ['restaurant_id'])) {
                $t->dropUnique(['restaurant_id']);
            }

            // Make NOT NULL
            $t->unsignedBigInteger('branch_id')->nullable(false)->change();

            // FK (skip if already exists)
            if (!$this->fkExists($table, 'branch_id')) {
                $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            }

            // Unique per branch (skip if already exists)
            if (!$this->uniqueExists($table, ['branch_id'])) {
                $t->unique('branch_id');
            }
        });
    }

    private function constrainHotelRooms(): void
    {
        $table = 'hotel_rooms';
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            // Drop old (restaurant_id, room_number) unique
            if ($this->uniqueExists($table, ['restaurant_id', 'room_number'])) {
                $t->dropUnique(['restaurant_id', 'room_number']);
            }

            $t->unsignedBigInteger('branch_id')->nullable(false)->change();

            if (!$this->fkExists($table, 'branch_id')) {
                $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            }

            if (!$this->uniqueExists($table, ['branch_id', 'room_number'])) {
                $t->unique(['branch_id', 'room_number']);
            }
        });
    }

    private function constrainHotelReservations(): void
    {
        $table = 'hotel_reservations';
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            // Drop global reservation_number unique so two branches can use
            // the same number format independently
            if ($this->uniqueExists($table, ['reservation_number'])) {
                $t->dropUnique(['reservation_number']);
            }

            $t->unsignedBigInteger('branch_id')->nullable(false)->change();

            if (!$this->fkExists($table, 'branch_id')) {
                $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            }

            // Unique per branch
            if (!$this->uniqueExists($table, ['branch_id', 'reservation_number'])) {
                $t->unique(['branch_id', 'reservation_number']);
            }
        });
    }

    private function constrainSimpleTables(): void
    {
        $simple = [
            'hotel_room_types',
            'hotel_guests',
            'hotel_payments',
            'hotel_expenses',
            'hotel_room_prices',
            'hotel_housekeeping_tasks',
        ];

        foreach ($simple as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedBigInteger('branch_id')->nullable(false)->change();

                if (!$this->fkExists($table, 'branch_id')) {
                    $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
                }
            });
        }
    }

    private function constrainRoomCharges(): void
    {
        $table = 'hotel_room_charges';
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            $t->unsignedBigInteger('branch_id')->nullable(false)->change();

            if (!$this->fkExists($table, 'branch_id')) {
                $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            }
        });
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function uniqueExists(string $table, array $columns): bool
    {
        $columnList = implode("','", $columns);
        $count = count($columns);

        $results = DB::select("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  IN ('{$columnList}')
              AND NON_UNIQUE   = 0
            GROUP BY INDEX_NAME
            HAVING COUNT(DISTINCT COLUMN_NAME) = ?
        ", [$table, $count]);

        return count($results) > 0;
    }

    private function fkExists(string $table, string $column): bool
    {
        $results = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA            = DATABASE()
              AND TABLE_NAME              = ?
              AND COLUMN_NAME             = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        return count($results) > 0;
    }

    private function safeDropForeign(Blueprint $blueprint, string $table, string $column): void
    {
        if ($this->fkExists($table, $column)) {
            $blueprint->dropForeign([$column]);
        }
    }
};

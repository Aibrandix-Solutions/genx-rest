<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Fix missing restaurant_id on hotel tables:
 * 1. Add restaurant_id to hotel_room_prices (was never added).
 * 2. Back-fill restaurant_id = NULL rows across all hotel tables
 *    that were created before the Livewire components were fixed
 *    to include restaurant_id in ::create() calls.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fallbackRestaurantId = DB::table('restaurants')->count() === 1
            ? (int) (DB::table('restaurants')->value('id') ?? 0)
            : 0;

        // ── 1. hotel_room_prices ──────────────────────────────────────────────
        if (!Schema::hasColumn('hotel_room_prices', 'restaurant_id')) {
            Schema::table('hotel_room_prices', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
                $table->index('restaurant_id');
            });
        }

        // Back-fill room_prices from their parent room_type
        DB::statement("
            UPDATE hotel_room_prices rp
            JOIN hotel_room_types rt ON rp.room_type_id = rt.id
            SET rp.restaurant_id = rt.restaurant_id
            WHERE rp.restaurant_id IS NULL
        ");

        // ── 2. Back-fill all other hotel tables with NULL restaurant_id ───────
        $tables = [
            'hotel_room_types',
            'hotel_rooms',
            'hotel_guests',
            'hotel_housekeeping_tasks',
            'hotel_payments',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'restaurant_id')) {
                continue;
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
        }

        // Reservations separately (uses reservation_number auto-gen)
        if (Schema::hasTable('hotel_reservations') && Schema::hasColumn('hotel_reservations', 'restaurant_id')) {
            if (Schema::hasColumn('hotel_reservations', 'branch_id') && Schema::hasTable('branches')) {
                DB::statement('
                    UPDATE hotel_reservations t
                    INNER JOIN branches b ON b.id = t.branch_id
                    SET t.restaurant_id = b.restaurant_id
                    WHERE t.restaurant_id IS NULL AND t.branch_id IS NOT NULL
                ');
            }

            if ($fallbackRestaurantId > 0) {
                DB::table('hotel_reservations')
                    ->whereNull('restaurant_id')
                    ->update(['restaurant_id' => $fallbackRestaurantId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotel_room_prices', 'restaurant_id')) {
            Schema::table('hotel_room_prices', function (Blueprint $table) {
                $table->dropIndex(['restaurant_id']);
                $table->dropColumn('restaurant_id');
            });
        }
    }
};

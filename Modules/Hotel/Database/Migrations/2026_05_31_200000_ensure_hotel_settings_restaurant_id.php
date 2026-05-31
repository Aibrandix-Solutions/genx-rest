<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Ensure hotel_settings is restaurant-scoped.
 * Idempotent — fixes DBs where create migration ran but convert_hotel_to_restaurant_scope did not.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_settings')) {
            return;
        }

        if (!Schema::hasColumn('hotel_settings', 'restaurant_id')) {
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('hotel_settings', 'branch_id') && Schema::hasTable('branches')) {
            DB::statement('
                UPDATE hotel_settings hs
                INNER JOIN branches b ON b.id = hs.branch_id
                SET hs.restaurant_id = b.restaurant_id
                WHERE hs.restaurant_id IS NULL AND hs.branch_id IS NOT NULL
            ');
        }

        // Single-restaurant DBs only — multi-tenant rows must resolve via branch_id above.
        if (DB::table('restaurants')->count() === 1) {
            $fallbackRestaurantId = (int) (DB::table('restaurants')->value('id') ?? 0);
            if ($fallbackRestaurantId > 0) {
                DB::table('hotel_settings')
                    ->whereNull('restaurant_id')
                    ->update(['restaurant_id' => $fallbackRestaurantId]);
            }
        }

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

        if (!Schema::hasColumn('hotel_settings', 'branch_id')) {
            $this->ensureRestaurantIdUnique();

            return;
        }

        try {
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
            });
        } catch (\Throwable) {
            // FK may already be removed.
        }

        try {
            Schema::table('hotel_settings', function (Blueprint $table) {
                $table->dropUnique(['branch_id']);
            });
        } catch (\Throwable) {
            // Unique may already be removed.
        }

        Schema::table('hotel_settings', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_settings', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });

        $this->ensureRestaurantIdUnique();
    }

    private function ensureRestaurantIdUnique(): void
    {
        if (!Schema::hasColumn('hotel_settings', 'restaurant_id')) {
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

    public function down(): void
    {
        // One-way data fix.
    }
};

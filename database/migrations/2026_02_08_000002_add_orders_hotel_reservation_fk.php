<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Ensures the FK constraint from orders.hotel_reservation_id → hotel_reservations.id
 * exists. The column itself is created by the main migration
 * (2026_02_06_162651_add_hotel_reservation_id_to_orders_table), but the FK is
 * deferred to here so it works even when Hotel module is enabled after the
 * initial deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_reservations') || !Schema::hasColumn('orders', 'hotel_reservation_id')) {
            return;
        }

        // Check if FK already exists (covers the case where the main migration added it)
        $fkExists = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'orders'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME LIKE '%hotel_reservation_id%'"
        ))->isNotEmpty();

        if (!$fkExists) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('hotel_reservation_id')
                      ->references('id')
                      ->on('hotel_reservations')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'hotel_reservation_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['hotel_reservation_id']);
                });
            } catch (\Throwable $e) {
                // safe to ignore
            }
        }
    }
};

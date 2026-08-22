<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'hotel_reservation_id')) {
                // Column only — FK constraint is added by Hotel module migration
                // so this migration works even when Hotel module is not yet enabled.
                $table->unsignedBigInteger('hotel_reservation_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('orders', 'room_service_charge')) {
                $table->decimal('room_service_charge', 10, 2)->default(0)->after('total');
            }
        });

        // Add FK only if hotel_reservations table already exists
        if (Schema::hasTable('hotel_reservations') && Schema::hasColumn('orders', 'hotel_reservation_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreign('hotel_reservation_id')
                          ->references('id')
                          ->on('hotel_reservations')
                          ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // FK may already exist — safe to ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop FK only if it exists
            try {
                $table->dropForeign(['hotel_reservation_id']);
            } catch (\Throwable $e) {
                // FK may not exist if Hotel module was never enabled
            }
            $table->dropColumn(['hotel_reservation_id', 'room_service_charge']);
        });
    }
};

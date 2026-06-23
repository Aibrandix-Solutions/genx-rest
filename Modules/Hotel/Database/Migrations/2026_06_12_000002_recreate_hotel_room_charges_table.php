<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Recreate hotel_room_charges when the table is missing from the InnoDB engine (MySQL 1932).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_reservations')) {
            return;
        }

        try {
            DB::statement('DROP TABLE IF EXISTS `hotel_room_charges`');
        } catch (\Throwable) {
            // Continue — table may be in a broken state that DROP still clears.
        }

        if (Schema::hasTable('hotel_room_charges')) {
            return;
        }

        Schema::create('hotel_room_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('hotel_reservations')->onDelete('cascade');
            $table->enum('charge_type', [
                'room_night',
                'restaurant',
                'minibar',
                'laundry',
                'service',
                'tax',
                'other',
            ]);
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->dateTime('charge_date');
            $table->timestamps();

            $table->index('reservation_id');
            $table->index(['charge_date', 'charge_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_room_charges');
    }
};

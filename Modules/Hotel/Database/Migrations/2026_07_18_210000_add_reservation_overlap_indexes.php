<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->index(
                ['room_id', 'status', 'check_in_date', 'checkout_date'],
                'hotel_reservations_room_overlap_idx'
            );
            $table->index(
                ['branch_id', 'status', 'check_in_date', 'checkout_date'],
                'hotel_reservations_branch_overlap_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropIndex('hotel_reservations_room_overlap_idx');
            $table->dropIndex('hotel_reservations_branch_overlap_idx');
        });
    }
};

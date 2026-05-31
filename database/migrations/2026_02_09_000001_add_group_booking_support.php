<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add max rooms per booking setting
        Schema::table('hotel_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_rooms_per_booking')->default(10)->after('enable_dynamic_pricing');
        });

        // Add group booking support to reservations
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->string('group_booking_id')->nullable()->after('reservation_number');
            $table->index('group_booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table) {
            $table->dropColumn('max_rooms_per_booking');
        });

        Schema::table('hotel_reservations', function (Blueprint $table) {
            $table->dropIndex(['group_booking_id']);
            $table->dropColumn('group_booking_id');
        });
    }
};

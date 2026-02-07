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
                $table->foreignId('hotel_reservation_id')->nullable()->constrained('hotel_reservations')->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('orders', 'room_service_charge')) {
                 $table->decimal('room_service_charge', 10, 2)->default(0)->after('total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['hotel_reservation_id']);
            $table->dropColumn(['hotel_reservation_id', 'room_service_charge']);
        });
    }
};

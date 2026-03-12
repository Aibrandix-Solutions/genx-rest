<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Historical fix: for delivery/dine-in orders, "ready_for_pickup" was incorrectly used to represent
        // the kitchen "food_ready" state. Pickup orders still use "ready_for_pickup".
        DB::table('orders')
            ->where('order_status', 'ready_for_pickup')
            ->whereIn('order_type', ['delivery', 'dine_in'])
            ->update(['order_status' => 'food_ready']);
    }

    public function down(): void
    {
        // No-op: reverting is not safe because "food_ready" is the canonical status for delivery/dine-in.
    }
};

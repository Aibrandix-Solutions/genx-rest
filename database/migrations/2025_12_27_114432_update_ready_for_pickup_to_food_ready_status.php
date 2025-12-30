<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all orders with 'ready_for_pickup' status for dine-in orders to 'food_ready'
        // For pickup and delivery orders, keep 'ready_for_pickup' as is
        DB::table('orders')
            ->where('order_status', 'ready_for_pickup')
            ->where('order_type', 'dine_in')
            ->update(['order_status' => 'food_ready']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert food_ready back to ready_for_pickup for dine-in orders
        DB::table('orders')
            ->where('order_status', 'food_ready')
            ->where('order_type', 'dine_in')
            ->update(['order_status' => 'ready_for_pickup']);
    }
};

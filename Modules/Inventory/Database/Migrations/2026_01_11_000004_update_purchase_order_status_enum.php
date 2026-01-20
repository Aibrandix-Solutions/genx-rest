<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First expand the enum to include both old and new values
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft', 'sent', 'received', 'partially_received', 'cancelled', 'ordered', 'pending') DEFAULT 'ordered'");
        
        // Convert legacy statuses to new ones
        DB::table('purchase_orders')->where('status', 'draft')->update(['status' => 'ordered']);
        DB::table('purchase_orders')->where('status', 'sent')->update(['status' => 'pending']);
        DB::table('purchase_orders')->where('status', 'partially_received')->update(['status' => 'received']);
        
        // Finally, restrict enum to only new values
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('ordered', 'pending', 'received', 'cancelled') DEFAULT 'ordered'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum values
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft', 'sent', 'received', 'partially_received', 'cancelled') DEFAULT 'draft'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend kot_items.status ENUM to include 'pending' and 'served'.
     * Original ENUM was ['cooking', 'ready'] — our multi-kitchen feature
     * maps KOT 'served' → item 'served' and 'pending_confirmation' → item 'pending'.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `kot_items` MODIFY COLUMN `status` ENUM('pending', 'cooking', 'ready', 'served') NULL");
    }

    public function down(): void
    {
        // Revert items with 'served'/'pending' back to null before shrinking enum
        DB::table('kot_items')->whereIn('status', ['served', 'pending'])->update(['status' => null]);
        DB::statement("ALTER TABLE `kot_items` MODIFY COLUMN `status` ENUM('cooking', 'ready') NULL");
    }
};

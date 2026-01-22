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
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('branch_id');
                $table->foreign('location_id')
                    ->references('id')
                    ->on('purchase_locations')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            }
        });

        // Best-effort backfill: map existing movements to a location matching branch (if any)
        // Requires a purchase_locations row per branch (type=branch)
        try {
            DB::statement("
                UPDATE inventory_movements m
                LEFT JOIN purchase_locations pl
                    ON pl.branch_id = m.branch_id
                SET m.location_id = COALESCE(m.location_id, pl.id)
                WHERE m.location_id IS NULL
            ");
        } catch (\Throwable $e) {
            // Ignore if tables or columns differ in some installs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });
    }
};

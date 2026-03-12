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
        Schema::table('inventory_transfers', function (Blueprint $table) {
            // Add location fields (nullable for backward compatibility)
            $table->unsignedBigInteger('source_location_id')->nullable()->after('source_branch_id');
            $table->unsignedBigInteger('destination_location_id')->nullable()->after('destination_branch_id');
            
            // Add foreign keys
            $table->foreign('source_location_id')
                ->references('id')
                ->on('purchase_locations')
                ->onDelete('set null')
                ->onUpdate('cascade');
                
            $table->foreign('destination_location_id')
                ->references('id')
                ->on('purchase_locations')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // Migrate existing data: Set location_id based on branch_id
        DB::statement("
            UPDATE inventory_transfers t
            LEFT JOIN purchase_locations pl_source ON pl_source.branch_id = t.source_branch_id AND pl_source.type = 'branch'
            LEFT JOIN purchase_locations pl_dest ON pl_dest.branch_id = t.destination_branch_id AND pl_dest.type = 'branch'
            SET 
                t.source_location_id = pl_source.id,
                t.destination_location_id = pl_dest.id
            WHERE t.source_location_id IS NULL OR t.destination_location_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropForeign(['source_location_id']);
            $table->dropForeign(['destination_location_id']);
            $table->dropColumn(['source_location_id', 'destination_location_id']);
        });
    }
};

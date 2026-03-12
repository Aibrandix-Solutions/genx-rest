<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            // Add location_id column
            $table->unsignedBigInteger('location_id')->nullable()->after('branch_id');
            $table->foreign('location_id')->references('id')->on('purchase_locations')->onDelete('cascade');
            
            // Add index for faster queries
            $table->index(['location_id', 'inventory_item_id']);
        });

        // Migrate existing data: Map branch_id to corresponding location_id
        // Find purchase_locations with type='branch' and matching branch_id
        DB::statement("
            UPDATE inventory_stocks s
            INNER JOIN purchase_locations pl ON pl.branch_id = s.branch_id AND pl.type = 'branch'
            SET s.location_id = pl.id
            WHERE s.location_id IS NULL
        ");

        // For any remaining stocks without location (edge case: branch doesn't have a location yet)
        // Create a default location for each branch
        $branchesWithoutLocation = DB::table('inventory_stocks')
            ->select('branch_id')
            ->whereNull('location_id')
            ->groupBy('branch_id')
            ->get();

        foreach ($branchesWithoutLocation as $branchRecord) {
            $branchId = $branchRecord->branch_id;
            
            // Get branch and restaurant info
            $branch = DB::table('branches')->where('id', $branchId)->first();
            
            if ($branch) {
                // Create location for this branch
                $locationId = DB::table('purchase_locations')->insertGetId([
                    'restaurant_id' => $branch->restaurant_id,
                    'branch_id' => $branchId,
                    'name' => $branch->name . ' Stock Location',
                    'type' => 'branch',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update inventory_stocks with new location_id
                DB::table('inventory_stocks')
                    ->where('branch_id', $branchId)
                    ->whereNull('location_id')
                    ->update(['location_id' => $locationId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id', 'inventory_item_id']);
            $table->dropColumn('location_id');
        });
    }
};

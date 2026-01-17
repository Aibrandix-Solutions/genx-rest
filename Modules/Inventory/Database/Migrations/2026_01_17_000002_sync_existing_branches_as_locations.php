<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sync existing branches as purchase locations
     */
    public function up(): void
    {
        $branches = \App\Models\Branch::all();
        
        foreach ($branches as $branch) {
            // Check if purchase location already exists for this branch
            $exists = \Modules\Inventory\Entities\PurchaseLocation::where('branch_id', $branch->id)
                ->where('type', 'branch')
                ->exists();
            
            if (!$exists) {
                \Modules\Inventory\Entities\PurchaseLocation::create([
                    'restaurant_id' => $branch->restaurant_id,
                    'branch_id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'type' => 'branch',
                    'is_active' => $branch->is_active ?? true,
                ]);
                
                echo "Created purchase location for branch: {$branch->name}\n";
            }
        }
        
        echo "Synced " . $branches->count() . " branches as purchase locations.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove auto-created branch locations
        $deleted = \Modules\Inventory\Entities\PurchaseLocation::where('type', 'branch')->delete();
        echo "Removed {$deleted} auto-created branch locations.\n";
    }
};

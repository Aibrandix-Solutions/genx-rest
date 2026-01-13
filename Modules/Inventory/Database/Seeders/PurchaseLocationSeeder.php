<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Entities\PurchaseLocation;
use App\Models\Restaurant;
use App\Models\Branch;

class PurchaseLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all restaurants
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            // Create a default warehouse location for each restaurant
            PurchaseLocation::create([
                'restaurant_id' => $restaurant->id,
                'name' => 'Main Warehouse',
                'type' => 'warehouse',
                'branch_id' => null,
                'address' => null,
                'is_active' => true,
            ]);

            // Create purchase locations for each branch
            $branches = Branch::where('restaurant_id', $restaurant->id)->get();
            
            foreach ($branches as $branch) {
                PurchaseLocation::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $branch->name,
                    'type' => 'branch',
                    'branch_id' => $branch->id,
                    'address' => $branch->address,
                    'is_active' => true,
                ]);
            }
        }
    }
}

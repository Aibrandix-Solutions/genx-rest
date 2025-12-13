<?php

use App\Models\Restaurant;
use App\Models\Role;
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
        // Add Cashier role for all existing restaurants
        $restaurants = Restaurant::all();
        
        foreach ($restaurants as $restaurant) {
            // Check if Cashier role already exists for this restaurant
            $cashierRole = Role::where('restaurant_id', $restaurant->id)
                ->where('name', 'Cashier_' . $restaurant->id)
                ->first();
            
            // Only create if it doesn't exist
            if (!$cashierRole) {
                Role::create([
                    'name' => 'Cashier_' . $restaurant->id,
                    'display_name' => 'Cashier',
                    'guard_name' => 'web',
                    'restaurant_id' => $restaurant->id
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Cashier roles for all restaurants
        Role::where('display_name', 'Cashier')->delete();
    }
};

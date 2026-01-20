<?php

use App\Models\Module;
use App\Models\Restaurant;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $inventoryModule = Module::where('name', 'Inventory')->first();

        if (!$inventoryModule) {
            $inventoryModule = Module::create(['name' => 'Inventory']);
        }

        // Create Show Inventory Dashboard permission
        $permission = Permission::firstOrCreate(
            ['name' => 'Show Inventory Dashboard', 'guard_name' => 'web'],
            ['module_id' => $inventoryModule->id]
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign to all restaurant admins and branch heads
        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
            $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }
            
            if ($branchHeadRole) {
                $branchHeadRole->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'Show Inventory Dashboard')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

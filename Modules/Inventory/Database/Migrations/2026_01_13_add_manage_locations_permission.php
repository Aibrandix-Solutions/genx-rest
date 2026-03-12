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
        $inventoryModule = Module::firstOrCreate(['name' => 'Inventory']);

        // Only create Manage Locations permission
        $permission = Permission::firstOrCreate(
            ['name' => 'Manage Locations', 'guard_name' => 'web'],
            ['module_id' => $inventoryModule->id]
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign to all restaurant admins
        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();

            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'Manage Locations')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

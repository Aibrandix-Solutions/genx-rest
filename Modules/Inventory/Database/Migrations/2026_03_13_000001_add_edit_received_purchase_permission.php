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

        $permission = Permission::firstOrCreate(
            ['name' => 'Edit Received Purchase', 'guard_name' => 'web'],
            ['module_id' => $inventoryModule->id]
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign only to Admin roles (not Branch Head) — admins get override ability
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
        Permission::where('name', 'Edit Received Purchase')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

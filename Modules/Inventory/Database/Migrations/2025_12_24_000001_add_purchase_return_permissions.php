<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\Restaurant;

return new class extends Migration
{
    public function up()
    {
        $inventoryModule = Module::firstOrCreate(['name' => 'Inventory']);

        $permissions = [
            ['guard_name' => 'web', 'name' => 'Create Purchase Return', 'module_id' => $inventoryModule->id],
            ['guard_name' => 'web', 'name' => 'Show Purchase Return', 'module_id' => $inventoryModule->id],
            ['guard_name' => 'web', 'name' => 'Update Purchase Return', 'module_id' => $inventoryModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Purchase Return', 'module_id' => $inventoryModule->id],
        ];

        // Use firstOrCreate to avoid duplicates
        $createdPermissions = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => 'web'],
                $permissionData
            );
            $createdPermissions[] = $permission;
        }

        // Clear permission cache to ensure fresh data
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
            $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

            if ($adminRole && !empty($createdPermissions)) {
                $adminRole->givePermissionTo($createdPermissions);
            }
            if ($branchHeadRole && !empty($createdPermissions)) {
                $branchHeadRole->givePermissionTo($createdPermissions);
            }
        }
    }

    public function down()
    {
        $permissions = [
            'Create Purchase Return',
            'Show Purchase Return',
            'Update Purchase Return',
            'Delete Purchase Return',
        ];

        Permission::whereIn('name', $permissions)->where('guard_name', 'web')->delete();
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\Restaurant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $inventoryModule = Module::firstOrCreate(['name' => 'Inventory']);

        $permissionNames = [
            'Show Stock Transfer',
            'Create Stock Transfer',
            'Update Stock Transfer',
            'Delete Stock Transfer',
            'Cancel Stock Transfer',
        ];

        $permissions = [];
        foreach ($permissionNames as $permissionName) {
            $permissions[] = Permission::firstOrCreate(
                [
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'module_id' => $inventoryModule->id,
                ]
            );
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
            $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

            if ($adminRole) {
                foreach ($permissions as $permission) {
                    if (!$adminRole->hasPermissionTo($permission)) {
                        $adminRole->givePermissionTo($permission);
                    }
                }
            }
            if ($branchHeadRole) {
                foreach ($permissions as $permission) {
                    if (!$branchHeadRole->hasPermissionTo($permission)) {
                        $branchHeadRole->givePermissionTo($permission);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $inventoryModule = Module::where('name', 'Inventory')->first();

        if ($inventoryModule) {
            Permission::where('module_id', $inventoryModule->id)
                ->whereIn('name', ['Show Stock Transfer', 'Create Stock Transfer', 'Update Stock Transfer', 'Delete Stock Transfer', 'Cancel Stock Transfer'])
                ->delete();
        }
    }
};

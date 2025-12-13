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

        $permission = Permission::firstOrCreate(
            [
                'name' => 'Cancel Stock Transfer',
                'guard_name' => 'web',
                'module_id' => $inventoryModule->id,
            ]
        );

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
            $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

            if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
            if ($branchHeadRole && !$branchHeadRole->hasPermissionTo($permission)) {
                $branchHeadRole->givePermissionTo($permission);
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
                ->where('name', 'Cancel Stock Transfer')
                ->delete();
        }
    }
};


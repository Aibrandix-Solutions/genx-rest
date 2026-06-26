<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\Restaurant;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $reportModule = Module::where('name', 'Report')->first();

        if (!$reportModule) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['guard_name' => 'web', 'name' => 'View Activity Log'],
            ['module_id' => $reportModule->id]
        );

        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            foreach (['Admin', 'Branch Head'] as $rolePrefix) {
                $role = Role::where('name', $rolePrefix . '_' . $restaurant->id)->first();

                if ($role) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'View Activity Log')
            ->where('guard_name', 'web')
            ->delete();
    }
};

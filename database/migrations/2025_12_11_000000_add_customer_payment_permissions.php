<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $customerModule = Module::where('name', 'Customer')->first();

        if ($customerModule) {
            $permissions = [
                Permission::firstOrCreate([
                    'guard_name' => 'web',
                    'name' => 'Create Payment',
                    'module_id' => $customerModule->id
                ]),
            ];

            // Assign permissions to roles
            $adminRole = Role::where('display_name', 'Admin')->get();
            $branchHeadRole = Role::where('display_name', 'Branch Head')->get();

            foreach ($adminRole as $role) {
                $role->givePermissionTo($permissions);
            }

            foreach ($branchHeadRole as $role) {
                $role->givePermissionTo($permissions);
            }

            // Clear permission cache
            app('cache')->forget('spatie.permission.cache');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $customerModule = Module::where('name', 'Customer')->first();

        if ($customerModule) {
            Permission::where('module_id', $customerModule->id)
                ->whereIn('name', ['Create Payment'])
                ->delete();

            app('cache')->forget('spatie.permission.cache');
        }
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $kotModule = DB::table('modules')->where('name', 'KOT')->first();

        if ($kotModule) {
            // Insert permission and get ID
            $permissionId = DB::table('permissions')->insertGetId([
                'guard_name' => 'web',
                'name' => 'Delete KOT Item',
                'module_id' => $kotModule->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign to Admin role
            $adminRole = DB::table('roles')->where('name', 'Admin_1')->first();
            if ($adminRole) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRole->id
                ]);
            }
        }
    }

    public function down(): void
    {
        // Delete the permission (cascading delete usually handles role_has_permissions, 
        // but explicit cleanup is safer if foreign keys aren't strict)
        $permission = DB::table('permissions')->where('name', 'Delete KOT Item')->first();
        
        if ($permission) {
             DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
             DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $orderModule = DB::table('modules')->where('name', 'Order')->first();

        if (!$orderModule) {
            return;
        }

        // Only insert if it doesn't already exist
        $existing = DB::table('permissions')->where('name', 'Edit Billed Order')->first();

        if ($existing) {
            return;
        }

        $permissionId = DB::table('permissions')->insertGetId([
            'guard_name' => 'web',
            'name' => 'Edit Billed Order',
            'module_id' => $orderModule->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign to all Admin and Branch Head roles
        $roles = DB::table('roles')
            ->where('name', 'LIKE', 'Admin\_%')
            ->orWhere('name', 'LIKE', 'Branch Head\_%')
            ->get();

        $inserts = [];
        foreach ($roles as $role) {
            $inserts[] = [
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ];
        }

        if (!empty($inserts)) {
            DB::table('role_has_permissions')->insert($inserts);
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('name', 'Edit Billed Order')->first();

        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};

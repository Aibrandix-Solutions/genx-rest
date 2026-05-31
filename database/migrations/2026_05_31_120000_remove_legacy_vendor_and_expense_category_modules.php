<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove legacy package-module rows (not Nwidart plugins).
     * Expense category permissions stay grouped under the Expense module.
     */
    public function up(): void
    {
        $expenseModule = DB::table('modules')->where('name', 'Expense')->first();

        foreach (Module::DEPRECATED_PACKAGE_MODULE_NAMES as $name) {
            $legacy = DB::table('modules')->where('name', $name)->first();

            if (!$legacy) {
                continue;
            }

            if ($expenseModule) {
                DB::table('permissions')
                    ->where('module_id', $legacy->id)
                    ->update(['module_id' => $expenseModule->id]);
            } else {
                $permissionIds = DB::table('permissions')
                    ->where('module_id', $legacy->id)
                    ->pluck('id');

                if ($permissionIds->isNotEmpty()) {
                    DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                    DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                    DB::table('permissions')->whereIn('id', $permissionIds)->delete();
                }
            }

            DB::table('package_modules')->where('module_id', $legacy->id)->delete();
            DB::table('modules')->where('id', $legacy->id)->delete();
        }
    }

    public function down(): void
    {
        // Legacy rows are not restored.
    }
};

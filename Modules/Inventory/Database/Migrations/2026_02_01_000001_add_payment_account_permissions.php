<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\Role;
use App\Models\Restaurant;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $paymentAccountsModule = Module::firstOrCreate(['name' => 'Payment Accounts']);

        $permissions = [
            'Show Payment Account',
            'Create Payment Account',
            'Update Payment Account',
            'Delete Payment Account',
            'Show Payment Account Report',
            'Show Payment Account Balance Sheet',
            'Show Payment Account Trial Balance',
            'Show Payment Account Cash Flow',
        ];

        $created = [];
        foreach ($permissions as $name) {
            $created[] = Permission::firstOrCreate(
                ['guard_name' => 'web', 'name' => $name],
                ['module_id' => $paymentAccountsModule->id]
            );
        }

        $restaurants = Restaurant::select('id')->get();

        foreach ($restaurants as $restaurant) {
            $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
            $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

            if ($adminRole) {
                $adminRole->givePermissionTo($created);
            }
            if ($branchHeadRole) {
                $branchHeadRole->givePermissionTo($created);
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'Show Payment Account',
            'Create Payment Account',
            'Update Payment Account',
            'Delete Payment Account',
            'Show Payment Account Report',
            'Show Payment Account Balance Sheet',
            'Show Payment Account Trial Balance',
            'Show Payment Account Cash Flow',
        ])->where('guard_name', 'web')->delete();
    }
};

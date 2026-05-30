<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Retrieve modules by name
        $menuModule = Module::where('name', 'Menu')->first();
        $menuItemModule = Module::where('name', 'Menu Item')->first();
        $itemCategoryModule = Module::where('name', 'Item Category')->first();
        $areaModule = Module::where('name', 'Area')->first();
        $tableModule = Module::where('name', 'Table')->first();
        $reservationModule = Module::where('name', 'Reservation')->first();
        $kotModule = Module::where('name', 'KOT')->first();
        $orderModule = Module::where('name', 'Order')->first();
        $customerModule = Module::where('name', 'Customer')->first();
        $staffModule = Module::where('name', 'Staff')->first();
        $paymentModule = Module::where('name', 'Payment')->first();
        $reportModule = Module::where('name', 'Report')->first();
        $settingsModule = Module::where('name', 'Settings')->first();
        $deliveryExecutiveModule = Module::where('name', 'Delivery Executive')->first();
        $waiterRequestModule = Module::where('name', 'Waiter Request')->first();
        $expenseModule = Module::where('name', 'Expense')->first();
        $vendorModule = Module::where('name', 'Vendor')->first();
        $expenseCategoryModule = Module::where('name', 'Expense Category')->first();
        $inventoryModule = Module::where('name', 'Inventory')->first();
        $hrmModule = Module::where('name', 'HRM')->first();

        // Check if modules exist before accessing their IDs
        if (!$menuModule || !$menuItemModule || !$itemCategoryModule || !$areaModule || !$tableModule || 
            !$reservationModule || !$kotModule || !$orderModule || !$customerModule || !$staffModule || 
            !$paymentModule || !$reportModule || !$settingsModule || !$deliveryExecutiveModule || 
            !$waiterRequestModule || !$expenseModule || !$vendorModule || !$expenseCategoryModule || !$inventoryModule || !$hrmModule) {
            
            // Log error or handle missing modules gracefully
            // For now, we will skip seeding permissions for missing modules or you might want to run ModuleSeeder
            // echo "Some modules are missing. Please run ModuleSeeder first.\n";
            // return;
        }

        // Define permissions to insert
        $permissions = [];

        if ($menuModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Menu', 'module_id' => $menuModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Menu', 'module_id' => $menuModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Menu', 'module_id' => $menuModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Menu', 'module_id' => $menuModule->id];
        }

        if ($menuItemModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Menu Item', 'module_id' => $menuItemModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Menu Item', 'module_id' => $menuItemModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Menu Item', 'module_id' => $menuItemModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Menu Item', 'module_id' => $menuItemModule->id];
        }

        if ($itemCategoryModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Item Category', 'module_id' => $itemCategoryModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Item Category', 'module_id' => $itemCategoryModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Item Category', 'module_id' => $itemCategoryModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Item Category', 'module_id' => $itemCategoryModule->id];
        }

        if ($areaModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Area', 'module_id' => $areaModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Area', 'module_id' => $areaModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Area', 'module_id' => $areaModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Area', 'module_id' => $areaModule->id];
        }

        if ($tableModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Table', 'module_id' => $tableModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Table', 'module_id' => $tableModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Table', 'module_id' => $tableModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Table', 'module_id' => $tableModule->id];
        }

        if ($reservationModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Reservation', 'module_id' => $reservationModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Reservation', 'module_id' => $reservationModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Reservation', 'module_id' => $reservationModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Reservation', 'module_id' => $reservationModule->id];
        }

        if ($kotModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage KOT', 'module_id' => $kotModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete KOT Item', 'module_id' => $kotModule->id];
        }

        if ($orderModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Order', 'module_id' => $orderModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Order', 'module_id' => $orderModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Order', 'module_id' => $orderModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Order', 'module_id' => $orderModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Edit Billed Order', 'module_id' => $orderModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Redeem Reward Points', 'module_id' => $orderModule->id];
        }

        if ($customerModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Customer', 'module_id' => $customerModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Customer', 'module_id' => $customerModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Customer', 'module_id' => $customerModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Customer', 'module_id' => $customerModule->id];
        }

        if ($staffModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Staff Member', 'module_id' => $staffModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Staff Member', 'module_id' => $staffModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Staff Member', 'module_id' => $staffModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Staff Member', 'module_id' => $staffModule->id];
        }

        if ($deliveryExecutiveModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Delivery Executive', 'module_id' => $deliveryExecutiveModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Delivery Executive', 'module_id' => $deliveryExecutiveModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Delivery Executive', 'module_id' => $deliveryExecutiveModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Delivery Executive', 'module_id' => $deliveryExecutiveModule->id];
        }

        if ($paymentModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Payments', 'module_id' => $paymentModule->id];
        }

        if ($reportModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Reports', 'module_id' => $reportModule->id];
        }

        if ($settingsModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Settings', 'module_id' => $settingsModule->id];
        }

        if ($waiterRequestModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Waiter Request', 'module_id' => $waiterRequestModule->id];
        }

        if ($expenseModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Expense', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Expense', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Expense', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Expense', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Expense Category', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Expense Category', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Expense Category', 'module_id' => $expenseModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Expense Category', 'module_id' => $expenseModule->id];
        }

        if ($inventoryModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Inventory Item', 'module_id' => $inventoryModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Inventory Item', 'module_id' => $inventoryModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Inventory Item', 'module_id' => $inventoryModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Inventory Item', 'module_id' => $inventoryModule->id];
        }

        if ($hrmModule) {
            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Department', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Department', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Department', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Department', 'module_id' => $hrmModule->id];

            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Designation', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Designation', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Designation', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Designation', 'module_id' => $hrmModule->id];

            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Employee', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Employee', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Employee', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Employee', 'module_id' => $hrmModule->id];

            $permissions[] = ['guard_name' => 'web', 'name' => 'Create Shift', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Show Shift', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Update Shift', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Delete Shift', 'module_id' => $hrmModule->id];

            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Attendance', 'module_id' => $hrmModule->id];

            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Shift Assignments', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Leave Types', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Leave Requests', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Holidays', 'module_id' => $hrmModule->id];
            $permissions[] = ['guard_name' => 'web', 'name' => 'Manage Payroll', 'module_id' => $hrmModule->id];
        }

        if (!empty($permissions)) {
            // Insert permissions into the database
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                    ['module_id' => $permission['module_id']]
                );
            }
        }

        // Ensure restaurant Admin roles get HRM permissions by default (non-destructive).
        if ($hrmModule) {
            $hrmPermissions = Permission::where('module_id', $hrmModule->id)->pluck('name')->toArray();

            if (!empty($hrmPermissions)) {
                $adminRoles = AppRole::withoutGlobalScopes()
                    ->where('display_name', 'Admin')
                    ->get();

                foreach ($adminRoles as $adminRole) {
                    $adminRole->givePermissionTo($hrmPermissions);
                }
            }
        }
    }

}

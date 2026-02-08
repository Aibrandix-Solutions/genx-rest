<?php

namespace Modules\Hotel\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HotelPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get or create Hotel module row
        $hotelModule = Module::firstOrCreate(
            ['name' => 'Hotel']
        );

        // Define hotel permissions with module_id
        $permissionNames = [
            // Dashboard
            'view_hotel_dashboard',
            
            // Room Types
            'view_hotel_room_types',
            'create_room_type',
            'edit_room_type',
            'delete_room_type',
            
            // Rooms
            'view_hotel_rooms',
            'create_room',
            'edit_room',
            'delete_room',
            'change_room_status',
            
            // Reservations
            'view_hotel_reservations',
            'create_reservation',
            'edit_reservation',
            'delete_reservation',
            'check_in_guest',
            'check_out_guest',
            
            // Guests
            'view_hotel_guests',
            'create_guest',
            'edit_guest',
            'delete_guest',
            
            // Billing
            'view_hotel_billing',
            'add_room_charge',
            'edit_room_charge',
            'delete_room_charge',
            'process_hotel_payment',
            'refund_reservation',
            
            // Housekeeping
            'view_hotel_housekeeping',
            'create_housekeeping_task',
            'assign_housekeeping_task',
            'complete_housekeeping_task',
            
            // Reports
            'view_hotel_reports',
            
            // Settings
            'manage_hotel_settings',
            'manage_room_prices',
            'manage_room_service',
        ];

        // Create permissions with module_id
        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['module_id' => $hotelModule->id]
            );
        }

        // Grant all permissions to ALL admin roles (across all restaurants)
        $adminRoles = \App\Models\Role::withoutGlobalScopes()
            ->where('display_name', 'Admin')
            ->get();

        if ($adminRoles->count() > 0) {
            foreach ($adminRoles as $adminRole) {
                $adminRole->givePermissionTo($permissionNames);
            }
            $this->command->info('Granted Hotel permissions to ' . $adminRoles->count() . ' admin role(s).');
        } else {
            $this->command->warn('No admin roles found.');
        }

        $this->command->info('Hotel permissions created successfully!');
    }
}

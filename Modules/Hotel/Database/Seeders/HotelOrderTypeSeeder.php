<?php

namespace Modules\Hotel\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderType;
use App\Models\Branch;

class HotelOrderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $branches = Branch::all();

        foreach ($branches as $branch) {
            OrderType::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'slug' => 'room_service'
                ],
                [
                    'order_type_name' => 'Room Service',
                    'type' => 'room_service',
                    'is_active' => true,
                ]
            );
        }
        
        $this->command->info('Room Service order types seeded for ' . $branches->count() . ' branches.');
    }
}

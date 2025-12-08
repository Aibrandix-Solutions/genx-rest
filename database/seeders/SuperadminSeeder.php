<?php

namespace Database\Seeders;

use App\Models\User;
use App\Observers\LanguageSettingObserver;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperadminSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'], ['display_name' => 'Super Admin']);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
            'name' => 'Emma Holden',
            'password' => bcrypt(123456)
            ]
        );

        if (!$user->hasRole('Super Admin')) {
        $user->assignRole('Super Admin');
        }

    }

}

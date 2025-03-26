<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Staff;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create the admin user
        $adminUser = User::create([
            'name' => 'System Admin',
            'email' => 'admin@school.com',
            'password' => Hash::make('password123'),
            'must_change_password' => true,
        ]);

        // Attach the 'admin' role
        $adminRole = Role::where('name', 'admin')->first();
        $adminUser->roles()->attach($adminRole);

        // Create staff bio linked to this user
        Staff::create([
            'user_id' => $adminUser->id,
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@school.com',
            'phone_number' => '0700000000',
            'status' => 'active',
        ]);
    }
}

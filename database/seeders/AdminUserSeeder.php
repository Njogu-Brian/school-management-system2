<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Ensure admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create or update the admin user
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'must_change_password' => true,
            ]
        );

        // Assign the admin role
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }

        // ✅ Give admin all permissions
        $adminRole->syncPermissions(Permission::all());

        // Create or update staff bio
        Staff::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'staff_id' => 'ADM-001',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'email' => 'admin@school.com',
                'phone_number' => '0700000000',
                'status' => 'active',
            ]
        );
    }
}

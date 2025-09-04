<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Define modules and actions
        $modules = [
            'students', 'staff', 'attendance',
            'finance', 'settings', 'transport',
            'communication', 'academics', 'admissions',
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        // Create permissions
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}"]);
            }
        }

        // Ensure admin role exists
        $admin = Role::firstOrCreate(['name' => 'admin']);

        // Give admin all permissions
        $admin->syncPermissions(Permission::all());
    }
}

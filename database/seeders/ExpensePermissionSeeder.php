<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExpensePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'expense.create',
            'expense.submit',
            'expense.approve',
            'expense.pay',
            'expense.view',
            'expense.report',
            'voucher.manage',
            'vendor.manage',
            'expense.category.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $grants = [
            'Secretary' => ['expense.create', 'expense.submit', 'expense.view'],
            'Finance Officer' => $permissions,
            'Accountant' => $permissions,
            'Admin' => $permissions,
            'Super Admin' => $permissions,
        ];

        foreach ($grants as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }
    }
}

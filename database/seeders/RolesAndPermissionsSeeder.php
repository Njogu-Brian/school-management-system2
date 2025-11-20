<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------
        // Define roles
        // ---------------------------
        $roles = [
            'Super Admin',
            'Admin',
            'Secretary',
            'Teacher',
            'Supervisor',
            'Driver',
            'Parent',
            'Student',
            'Accountant',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // ---------------------------
        // Define permissions
        // ---------------------------
        $permissions = [

            // Dashboards
            'admin.dashboard',
            'teacher.dashboard',
            'student.dashboard',
            'transport.index',

            // Students
            'students.view',
            'students.create',
            'students.edit',
            'students.delete',

            // Staff
            'staff.view',
            'staff.create',
            'staff.edit',
            'staff.delete',
            'manage staff',

            // Attendance
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',

            // Communication
            'communication.view',
            'communication.create',
            'communication.edit',
            'communication.delete',

            // Settings
            'settings.view',
            'settings.create',
            'settings.edit',
            'settings.delete',
            'manage settings',

            // Admissions
            'admissions.view',
            'admissions.create',
            'admissions.edit',
            'admissions.delete',

            // Transport
            'transport.view',
            'transport.create',
            'transport.edit',
            'transport.delete',
            'manage transport',

            // Finance
            'finance.view',
            'finance.create',
            'finance.edit',
            'finance.delete',
            'manage finance',

            // Academics
            'academics.view',
            'academics.create',
            'academics.edit',
            'academics.delete',
            'manage students',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // ---------------------------
        // Assign permissions to roles
        // ---------------------------

        // Super Admin → everything
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $superAdmin->syncPermissions(Permission::all());

        // Admin
        $admin = Role::where('name', 'Admin')->first();
        $admin->syncPermissions([
            'admin.dashboard',
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.delete',
            'communication.view', 'communication.create', 'communication.edit', 'communication.delete',
            'settings.view', 'settings.edit',
            'finance.view', 'finance.create', 'finance.edit',
            'academics.view', 'academics.create', 'academics.edit',
        ]);

        // Secretary
        $secretary = Role::where('name', 'Secretary')->first();
        $secretary->syncPermissions([
            'admin.dashboard',
            'students.view', 'students.create', 'students.edit',
            'attendance.view', 'attendance.create',
            'communication.view', 'communication.create',
            'finance.view',
        ]);

        // Teacher
        $teacher = Role::where('name', 'Teacher')->first();
        $teacher->syncPermissions([
            'teacher.dashboard',
            'attendance.view', 'attendance.create',
            'students.view',
        ]);

        // Driver
        $driver = Role::where('name', 'Driver')->first();
        $driver->syncPermissions([
            'transport.index',
            'transport.view',
        ]);

        // Parent
        $parent = Role::where('name', 'Parent')->first();
        $parent->syncPermissions([
            'students.view',
            'communication.view',
        ]);

        // Student
        $student = Role::where('name', 'Student')->first();
        $student->syncPermissions([
            'student.dashboard',
            'students.view',
        ]);

        // Accountant
        $accountant = Role::where('name', 'Accountant')->first();
        $accountant->syncPermissions([
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete',
            'manage finance',
        ]);
    }
}

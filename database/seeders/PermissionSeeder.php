<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Convert module/feature format to Spatie Permission name format
        $permissions = [
            'communication.send_email',
            'communication.send_sms',
            'communication.logs',
            'communication.email_template',
            'communication.sms_template',
            'communication.announcements',

            'staff.manage_staff',
            'staff.upload_staff',

            'students.manage_students',

            'attendance.mark_attendance',
            'attendance.view_attendance',

            'transport.vehicles',
            'transport.routes',
            'transport.trips',

            'kitchen.daily_summary',

            'academics.classrooms',
            'academics.streams',
            'academics.student_categories',

            'admissions.online_admission',

            'settings.general',
            'settings.regional',
            'settings.branding',
            'settings.roles_permissions',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }
    }
}

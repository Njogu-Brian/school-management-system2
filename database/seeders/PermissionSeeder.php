<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Use Spatie permission names
        $data = [
            ['name' => 'communication.send_email'],
            ['name' => 'communication.send_sms'],
            ['name' => 'communication.logs'],
            ['name' => 'communication.email_template'],
            ['name' => 'communication.sms_template'],
            ['name' => 'communication.announcements'],

            ['name' => 'staff.manage_staff'],
            ['name' => 'staff.upload_staff'],

            ['name' => 'students.manage_students'],

            ['name' => 'attendance.mark_attendance'],
            ['name' => 'attendance.view_attendance'],

            ['name' => 'transport.vehicles'],
            ['name' => 'transport.routes'],
            ['name' => 'transport.trips'],

            ['name' => 'kitchen.daily_summary'],

            ['name' => 'academics.classrooms'],
            ['name' => 'academics.streams'],
            ['name' => 'academics.student_categories'],

            ['name' => 'admissions.online_admission'],

            ['name' => 'settings.general'],
            ['name' => 'settings.regional'],
            ['name' => 'settings.branding'],
            ['name' => 'settings.roles_permissions'],
        ];

        foreach ($data as $perm) {
            Permission::firstOrCreate($perm);
        }
    }
}

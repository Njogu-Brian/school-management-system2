<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['module' => 'communication', 'feature' => 'send_email'],
            ['module' => 'communication', 'feature' => 'send_sms'],
            ['module' => 'communication', 'feature' => 'logs'],
            ['module' => 'communication', 'feature' => 'email_template'],
            ['module' => 'communication', 'feature' => 'sms_template'],
            ['module' => 'communication', 'feature' => 'announcements'],

            ['module' => 'staff', 'feature' => 'manage_staff'],
            ['module' => 'staff', 'feature' => 'upload_staff'],

            ['module' => 'students', 'feature' => 'manage_students'],

            ['module' => 'attendance', 'feature' => 'mark_attendance'],
            ['module' => 'attendance', 'feature' => 'view_attendance'],

            ['module' => 'transport', 'feature' => 'vehicles'],
            ['module' => 'transport', 'feature' => 'routes'],
            ['module' => 'transport', 'feature' => 'trips'],

            ['module' => 'kitchen', 'feature' => 'daily_summary'],

            ['module' => 'academics', 'feature' => 'classrooms'],
            ['module' => 'academics', 'feature' => 'streams'],
            ['module' => 'academics', 'feature' => 'student_categories'],

            ['module' => 'admissions', 'feature' => 'online_admission'],

            ['module' => 'settings', 'feature' => 'general'],
            ['module' => 'settings', 'feature' => 'regional'],
            ['module' => 'settings', 'feature' => 'branding'],
            ['module' => 'settings', 'feature' => 'roles_permissions'],
        ];

        foreach ($data as $perm) {
            Permission::firstOrCreate($perm);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TeacherPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // --- Permissions used in nav-teacher + routes/middleware ---
        $teacherPermissions = [
            // dashboards
            'dashboard.teacher.view',

            // attendance
            'attendance.view',
            'attendance.create',

            // exam marks
            'exam_marks.view',
            'exam_marks.create',

            // report cards & skills / remarks
            'report_cards.view',
            'report_card_skills.edit',
            'report_cards.remarks.edit',

            // homework
            'homework.view',
            'homework.create',
            'homework.edit',

            // digital diaries
            'diaries.view',
            'diaries.create',
            'diaries.edit',

            // student behaviours
            'student_behaviours.view',
            'student_behaviours.create',
            'student_behaviours.edit',
        ];

        // Ensure each permission exists
        foreach ($teacherPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        // Ensure roles exist
        $teacher    = Role::firstOrCreate(['name' => 'Teacher',     'guard_name' => $guard]);
        $admin      = Role::firstOrCreate(['name' => 'Admin',       'guard_name' => $guard]);
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $secretary  = Role::firstOrCreate(['name' => 'Secretary',   'guard_name' => $guard]);

        // Give Teacher only the teacher set
        $teacher->syncPermissions($teacherPermissions);

        // Give Admin/Super Admin/Secretary EVERYTHING (optional but handy)
        $allPerms = Permission::pluck('name')->all();
        $admin->syncPermissions($allPerms);
        $superAdmin->syncPermissions($allPerms);
        $secretary->syncPermissions($allPerms);
    }
}

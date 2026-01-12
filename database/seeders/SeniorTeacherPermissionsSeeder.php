<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SeniorTeacherPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // Senior Teacher specific permissions
        $seniorTeacherPermissions = [
            // Dashboard
            'dashboard.senior_teacher.view',
            
            // All teacher permissions
            'attendance.view',
            'attendance.create',
            'exam_marks.view',
            'exam_marks.create',
            'report_cards.view',
            'report_card_skills.edit',
            'report_cards.remarks.edit',
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',
            'diaries.view',
            'diaries.create',
            'diaries.edit',
            'diaries.delete',
            'student_behaviours.view',
            'student_behaviours.create',
            'student_behaviours.edit',
            'student_behaviours.delete',
            
            // Timetable (view and edit for supervised classes)
            'timetable.view',
            'timetable.edit',
            
            // Student data for supervised classes/students
            'students.view',
            'students.details.view',
            
            // Transport (view only for supervised students)
            'transport.view',
            
            // Finance (view only - fee balances, cannot collect/edit/invoice/discount)
            'finance.fee_balances.view',
            
            // Academics (full view for supervised classes)
            'academics.view',
            'subjects.view',
            'classrooms.view',
            'exams.view',
            
            // Supervisory specific
            'senior_teacher.supervisory_classes.view',
            'senior_teacher.supervised_staff.view',
        ];

        // Ensure each permission exists
        foreach ($seniorTeacherPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        // Ensure roles exist
        $seniorTeacher = Role::firstOrCreate(['name' => 'Senior Teacher', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);

        // Assign permissions to Senior Teacher role
        $seniorTeacher->syncPermissions($seniorTeacherPermissions);

        // Admin and Super Admin get all permissions
        $adminPermissions = Permission::all();
        $admin->givePermissionTo($adminPermissions);
        $superAdmin->givePermissionTo($adminPermissions);
    }
}


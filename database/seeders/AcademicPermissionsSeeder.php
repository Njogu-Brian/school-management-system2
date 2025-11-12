<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AcademicPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // Academic Module Permissions
        $academicPermissions = [
            // Schemes of Work
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'schemes_of_work.delete',
            'schemes_of_work.approve',

            // Lesson Plans
            'lesson_plans.view',
            'lesson_plans.create',
            'lesson_plans.edit',
            'lesson_plans.delete',

            // CBC Strands & Substrands (Admin only)
            'cbc_strands.view',
            'cbc_strands.manage',

            // Portfolio Assessments
            'portfolio_assessments.view',
            'portfolio_assessments.create',
            'portfolio_assessments.edit',
            'portfolio_assessments.delete',

            // Enhanced Report Cards
            'report_cards.view',
            'report_cards.create',
            'report_cards.edit',
            'report_cards.publish',
            'report_cards.skills.edit',
            'report_cards.remarks.edit',
            'report_cards.competencies.edit',
        ];

        // Create permissions
        foreach ($academicPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        // Get roles
        $teacher = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $secretary = Role::firstOrCreate(['name' => 'Secretary', 'guard_name' => $guard]);

        // Teacher permissions (restricted to own classes)
        $teacherPermissions = [
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'lesson_plans.view',
            'lesson_plans.create',
            'lesson_plans.edit',
            'portfolio_assessments.view',
            'portfolio_assessments.create',
            'portfolio_assessments.edit',
            'report_cards.view',
            'report_cards.skills.edit',
            'report_cards.remarks.edit',
            'report_cards.competencies.edit',
            'cbc_strands.view', // Can view but not manage
        ];

        // Admin/Secretary permissions (full access)
        $adminPermissions = [
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'schemes_of_work.delete',
            'schemes_of_work.approve',
            'lesson_plans.view',
            'lesson_plans.create',
            'lesson_plans.edit',
            'lesson_plans.delete',
            'cbc_strands.view',
            'cbc_strands.manage',
            'portfolio_assessments.view',
            'portfolio_assessments.create',
            'portfolio_assessments.edit',
            'portfolio_assessments.delete',
            'report_cards.view',
            'report_cards.create',
            'report_cards.edit',
            'report_cards.publish',
            'report_cards.skills.edit',
            'report_cards.remarks.edit',
            'report_cards.competencies.edit',
        ];

        // Assign permissions
        $teacher->givePermissionTo($teacherPermissions);
        $admin->givePermissionTo($adminPermissions);
        $secretary->givePermissionTo($adminPermissions);
        
        // Super Admin gets everything
        $superAdmin->givePermissionTo(Permission::all());
    }
}

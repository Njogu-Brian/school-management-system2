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
            // Subjects
            'subjects.view',
            'subjects.create',
            'subjects.edit',
            'subjects.delete',

            // Classrooms
            'classrooms.view',
            'classrooms.create',
            'classrooms.edit',
            'classrooms.delete',

            // Learning Areas
            'learning_areas.view',
            'learning_areas.create',
            'learning_areas.edit',
            'learning_areas.delete',
            'learning_areas.manage',

            // CBC Strands & Substrands
            'cbc_strands.view',
            'cbc_strands.create',
            'cbc_strands.edit',
            'cbc_strands.delete',
            'cbc_strands.manage',
            'cbc_substrands.view',
            'cbc_substrands.create',
            'cbc_substrands.edit',
            'cbc_substrands.delete',

            // Competencies
            'competencies.view',
            'competencies.create',
            'competencies.edit',
            'competencies.delete',

            // Schemes of Work
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'schemes_of_work.delete',
            'schemes_of_work.approve',
            'schemes_of_work.publish',
            'schemes_of_work.export_pdf',
            'schemes_of_work.export_excel',
            'schemes_of_work.generate',

            // Lesson Plans
            'lesson_plans.view',
            'lesson_plans.create',
            'lesson_plans.edit',
            'lesson_plans.delete',
            'lesson_plans.export_pdf',
            'lesson_plans.export_excel',

            // Advanced Exams
            'exams.view',
            'exams.create',
            'exams.edit',
            'exams.delete',
            'exams.publish',
            'exams.enter_marks',
            'exams.import_marks',
            'exams.export_marks',
            'exams.approve',
            'exams.calculate_grades',
            'exam_types.view',
            'exam_types.create',
            'exam_types.edit',
            'exam_types.delete',

            // Portfolio Assessments
            'portfolio_assessments.view',
            'portfolio_assessments.create',
            'portfolio_assessments.edit',
            'portfolio_assessments.delete',
            'portfolio_assessments.export_pdf',

            // Enhanced Report Cards
            'report_cards.view',
            'report_cards.create',
            'report_cards.edit',
            'report_cards.delete',
            'report_cards.publish',
            'report_cards.generate',
            'report_cards.export_pdf',
            'report_cards.export_excel',
            'report_cards.export_bulk',
            'report_cards.skills.edit',
            'report_cards.remarks.edit',
            'report_cards.competencies.edit',

            // Homework Diary
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',
            'homework.assign',
            'homework.mark',
            'homework.view_diary',
            'homework.submit',
            'homework.approve',

            // PDF/Excel Export
            'exports.pdf',
            'exports.excel',
            'exports.bulk',

            // Audit Logs (Admin only)
            'audit_logs.view',
            'audit_logs.export',

            // Curriculum Designs
            'curriculum_designs.view',
            'curriculum_designs.view_own',
            'curriculum_designs.create',
            'curriculum_designs.edit',
            'curriculum_designs.delete',

            // Curriculum AI Assistant
            'curriculum_assistant.use',
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
        $seniorTeacher = Role::firstOrCreate(['name' => 'Senior Teacher', 'guard_name' => $guard]);
        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => $guard]);
        $parent = Role::firstOrCreate(['name' => 'Parent', 'guard_name' => $guard]);
        $student = Role::firstOrCreate(['name' => 'Student', 'guard_name' => $guard]);

        // Teacher permissions (restricted to own classes)
        $teacherPermissions = [
            'subjects.view',
            'classrooms.view',
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'schemes_of_work.export_pdf',
            'schemes_of_work.export_excel',
            'lesson_plans.view',
            'lesson_plans.create',
            'lesson_plans.edit',
            'lesson_plans.export_pdf',
            'lesson_plans.export_excel',
            'exams.view',
            'exams.create',
            'exams.edit',
            'exams.enter_marks',
            'exams.import_marks',
            'exams.export_marks',
            'exams.calculate_grades',
            'portfolio_assessments.view',
            'portfolio_assessments.create',
            'portfolio_assessments.edit',
            'report_cards.view',
            'report_cards.skills.edit',
            'report_cards.remarks.edit',
            'report_cards.competencies.edit',
            'report_cards.export_pdf',
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.assign',
            'homework.mark',
            'homework.view_diary',
            'cbc_strands.view',
            'cbc_substrands.view',
            'competencies.view',
            'learning_areas.view',
            'curriculum_designs.view_own',
            'curriculum_assistant.use',
            'exports.pdf',
            'exports.excel',
        ];

        // Senior Teacher permissions (can approve and publish)
        $seniorTeacherPermissions = array_merge($teacherPermissions, [
            'schemes_of_work.approve',
            'schemes_of_work.publish',
            'exams.publish',
            'exams.approve',
            'report_cards.publish',
            'report_cards.generate',
            'homework.approve',
            'curriculum_designs.view',
            'curriculum_designs.create',
            'curriculum_designs.edit',
            'curriculum_assistant.use',
        ]);

        // Admin/Secretary permissions (full access)
        $adminPermissions = [
            'subjects.view',
            'subjects.create',
            'subjects.edit',
            'subjects.delete',
            'classrooms.view',
            'classrooms.create',
            'classrooms.edit',
            'classrooms.delete',
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'schemes_of_work.delete',
            'schemes_of_work.approve',
            'schemes_of_work.publish',
            'schemes_of_work.export_pdf',
            'schemes_of_work.export_excel',
            'schemes_of_work.generate',
            'lesson_plans.view',
            'lesson_plans.create',
            'lesson_plans.edit',
            'lesson_plans.delete',
            'lesson_plans.export_pdf',
            'lesson_plans.export_excel',
            'exams.view',
            'exams.create',
            'exams.edit',
            'exams.delete',
            'exams.publish',
            'exams.enter_marks',
            'exams.import_marks',
            'exams.export_marks',
            'exams.approve',
            'exams.calculate_grades',
            'exam_types.view',
            'exam_types.create',
            'exam_types.edit',
            'exam_types.delete',
            'cbc_strands.view',
            'cbc_strands.create',
            'cbc_strands.edit',
            'cbc_strands.delete',
            'cbc_strands.manage',
            'cbc_substrands.view',
            'cbc_substrands.create',
            'cbc_substrands.edit',
            'cbc_substrands.delete',
            'competencies.view',
            'competencies.create',
            'competencies.edit',
            'competencies.delete',
            'learning_areas.view',
            'learning_areas.create',
            'learning_areas.edit',
            'learning_areas.delete',
            'learning_areas.manage',
            'portfolio_assessments.view',
            'portfolio_assessments.create',
            'portfolio_assessments.edit',
            'portfolio_assessments.delete',
            'portfolio_assessments.export_pdf',
            'report_cards.view',
            'report_cards.create',
            'report_cards.edit',
            'report_cards.delete',
            'report_cards.publish',
            'report_cards.generate',
            'report_cards.export_pdf',
            'report_cards.export_excel',
            'report_cards.export_bulk',
            'report_cards.skills.edit',
            'report_cards.remarks.edit',
            'report_cards.competencies.edit',
            'homework.view',
            'homework.create',
            'homework.edit',
            'homework.delete',
            'homework.assign',
            'homework.mark',
            'homework.view_diary',
            'homework.approve',
            'exports.pdf',
            'exports.excel',
            'exports.bulk',
            'audit_logs.view',
            'audit_logs.export',
            'curriculum_designs.view',
            'curriculum_designs.create',
            'curriculum_designs.edit',
            'curriculum_designs.delete',
            'curriculum_assistant.use',
        ];

        // Parent permissions
        $parentPermissions = [
            'homework.view_diary',
            'report_cards.view',
            'homework.view',
        ];

        // Student permissions
        $studentPermissions = [
            'homework.view',
            'homework.submit',
            'homework.view_diary',
            'report_cards.view',
        ];

        // Assign permissions
        $teacher->givePermissionTo($teacherPermissions);
        $seniorTeacher->givePermissionTo($seniorTeacherPermissions);
        $admin->givePermissionTo($adminPermissions);
        $secretary->givePermissionTo($adminPermissions);
        $parent->givePermissionTo($parentPermissions);
        $student->givePermissionTo($studentPermissions);
        
        // Super Admin gets everything
        $superAdmin->givePermissionTo(Permission::all());
    }
}

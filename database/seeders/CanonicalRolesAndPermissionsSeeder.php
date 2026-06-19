<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Single authoritative role → permission matrix for the web portal.
 * Safe to re-run: creates missing permissions/roles and syncs each role's final set.
 */
class CanonicalRolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Director',
            'Admin',
            'Secretary',
            'Academic Administrator',
            'Finance Officer',
            'Accountant',
            'Senior Teacher',
            'Deputy Senior Teacher',
            'Supervisor',
            'Teacher',
            'Driver',
            'Parent',
            'Student',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, self::GUARD);
        }

        $permissions = $this->allPermissions();
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        $all = Permission::where('guard_name', self::GUARD)->pluck('name')->all();

        $grants = [
            'Super Admin' => $all,
            'Director' => $all,
            'Admin' => $all,
            'Secretary' => $this->secretaryPermissions(),
            'Academic Administrator' => $this->academicAdminPermissions(),
            'Finance Officer' => $this->financePermissions(),
            'Accountant' => $this->financePermissions(),
            'Senior Teacher' => $this->seniorTeacherPermissions(),
            'Deputy Senior Teacher' => $this->seniorTeacherPermissions(),
            'Supervisor' => $this->teacherPermissions(),
            'Teacher' => $this->teacherPermissions(),
            'Driver' => ['transport.index', 'transport.view'],
            'Parent' => $this->parentPermissions(),
            'Student' => $this->studentPermissions(),
        ];

        foreach ($grants as $roleName => $perms) {
            $role = Role::findByName($roleName, self::GUARD);
            $role->syncPermissions($perms);
        }
    }

    private function allPermissions(): array
    {
        return array_values(array_unique(array_merge(
            $this->portalPermissions(),
            $this->academicPermissions(),
            $this->teacherNavPermissions(),
            $this->seniorTeacherExtras(),
            $this->expensePermissions(),
            $this->comprehensivePermissions(),
        )));
    }

    private function portalPermissions(): array
    {
        return [
            'admin.dashboard', 'teacher.dashboard', 'student.dashboard', 'transport.index',
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'manage staff',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.delete',
            'communication.view', 'communication.create', 'communication.edit', 'communication.delete',
            'communication.send_email', 'communication.send_sms', 'communication.logs',
            'communication.email_template', 'communication.sms_template', 'communication.announcements',
            'settings.view', 'settings.create', 'settings.edit', 'settings.delete', 'manage settings',
            'settings.general', 'settings.regional', 'settings.branding', 'settings.roles_permissions',
            'admissions.view', 'admissions.create', 'admissions.edit', 'admissions.delete',
            'admissions.online_admission',
            'transport.view', 'transport.create', 'transport.edit', 'transport.delete', 'manage transport',
            'transport.vehicles', 'transport.routes', 'transport.trips',
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'manage finance',
            'academics.view', 'academics.create', 'academics.edit', 'academics.delete', 'manage students',
            'academics.classrooms', 'academics.streams', 'academics.student_categories',
            'staff.manage_staff', 'staff.upload_staff', 'students.manage_students',
            'attendance.mark_attendance', 'attendance.view_attendance',
            'kitchen.daily_summary',
        ];
    }

    private function comprehensivePermissions(): array
    {
        return [
            'dashboard.view', 'students.manage', 'staff.manage',
            'attendance.mark', 'academics.manage', 'exams.manage',
            'report_cards.manage', 'finance.manage', 'transport.manage',
            'inventory.view', 'inventory.manage', 'events.manage', 'settings.manage',
            'extra_curricular.view', 'extra_curricular.create', 'extra_curricular.edit', 'extra_curricular.delete',
            'dashboard.teacher.view', 'dashboard.senior_teacher.view',
        ];
    }

    private function academicPermissions(): array
    {
        return [
            'subjects.view', 'subjects.create', 'subjects.edit', 'subjects.delete',
            'classrooms.view', 'classrooms.create', 'classrooms.edit', 'classrooms.delete',
            'learning_areas.view', 'learning_areas.create', 'learning_areas.edit', 'learning_areas.delete', 'learning_areas.manage',
            'cbc_strands.view', 'cbc_strands.create', 'cbc_strands.edit', 'cbc_strands.delete', 'cbc_strands.manage',
            'cbc_substrands.view', 'cbc_substrands.create', 'cbc_substrands.edit', 'cbc_substrands.delete',
            'competencies.view', 'competencies.create', 'competencies.edit', 'competencies.delete',
            'schemes_of_work.view', 'schemes_of_work.create', 'schemes_of_work.edit', 'schemes_of_work.delete',
            'schemes_of_work.approve', 'schemes_of_work.publish', 'schemes_of_work.export_pdf', 'schemes_of_work.export_excel', 'schemes_of_work.generate',
            'lesson_plans.view', 'lesson_plans.create', 'lesson_plans.edit', 'lesson_plans.delete',
            'lesson_plans.export_pdf', 'lesson_plans.export_excel',
            'exams.view', 'exams.create', 'exams.edit', 'exams.delete', 'exams.publish',
            'exams.enter_marks', 'exams.import_marks', 'exams.export_marks', 'exams.approve', 'exams.calculate_grades',
            'exam_types.view', 'exam_types.create', 'exam_types.edit', 'exam_types.delete',
            'portfolio_assessments.view', 'portfolio_assessments.create', 'portfolio_assessments.edit', 'portfolio_assessments.delete', 'portfolio_assessments.export_pdf',
            'report_cards.view', 'report_cards.create', 'report_cards.edit', 'report_cards.delete',
            'report_cards.publish', 'report_cards.generate', 'report_cards.export_pdf', 'report_cards.export_excel', 'report_cards.export_bulk',
            'report_cards.skills.edit', 'report_cards.remarks.edit', 'report_cards.competencies.edit',
            'homework.view', 'homework.create', 'homework.edit', 'homework.delete',
            'homework.assign', 'homework.mark', 'homework.view_diary', 'homework.submit', 'homework.approve',
            'exports.pdf', 'exports.excel', 'exports.bulk',
            'audit_logs.view', 'audit_logs.export',
            'curriculum_designs.view', 'curriculum_designs.view_own', 'curriculum_designs.create', 'curriculum_designs.edit', 'curriculum_designs.delete',
            'curriculum_assistant.use',
        ];
    }

    private function teacherNavPermissions(): array
    {
        return [
            'exam_marks.view', 'exam_marks.create',
            'report_card_skills.edit',
            'diaries.view', 'diaries.create', 'diaries.edit', 'diaries.delete',
            'student_behaviours.view', 'student_behaviours.create', 'student_behaviours.edit', 'student_behaviours.delete',
            'timetable.view', 'timetable.edit',
            'students.details.view',
            'inventory.view', 'student_requirements.view',
        ];
    }

    private function seniorTeacherExtras(): array
    {
        return [
            'finance.fee_balances.view',
            'senior_teacher.supervisory_classes.view',
            'senior_teacher.supervised_staff.view',
        ];
    }

    private function expensePermissions(): array
    {
        return [
            'expense.create', 'expense.submit', 'expense.approve', 'expense.pay',
            'expense.view', 'expense.report',
            'voucher.manage', 'vendor.manage', 'expense.category.manage',
        ];
    }

    private function secretaryPermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                'admin.dashboard', 'dashboard.view',
                'students.view', 'students.create', 'students.edit', 'students.manage', 'students.manage_students',
                'staff.view', 'staff.manage_staff',
                'attendance.view', 'attendance.create', 'attendance.mark', 'attendance.mark_attendance', 'attendance.view_attendance',
                'communication.view', 'communication.create', 'communication.send_email', 'communication.send_sms',
                'communication.announcements', 'communication.logs',
                'finance.view', 'finance.create', 'finance.edit', 'finance.manage',
                'academics.view', 'academics.manage',
                'transport.view', 'transport.manage', 'transport.vehicles', 'transport.routes', 'transport.trips',
                'settings.view', 'settings.general',
                'admissions.view', 'admissions.online_admission',
                'inventory.view', 'inventory.manage',
                'events.manage', 'extra_curricular.view', 'extra_curricular.create', 'extra_curricular.edit',
                'expense.create', 'expense.submit', 'expense.view',
            ],
            $this->adminAcademicPermissions(),
        )));
    }

    private function academicAdminPermissions(): array
    {
        return [
            'admin.dashboard', 'dashboard.view',
            'students.view', 'students.manage_students',
            'attendance.view', 'attendance.create', 'attendance.mark', 'attendance.mark_attendance',
            'academics.view', 'academics.manage',
            'communication.view',
            'classrooms.view', 'subjects.view', 'exams.view', 'report_cards.view',
            'extra_curricular.view',
        ];
    }

    private function financePermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                'admin.dashboard', 'dashboard.view',
                'finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.manage', 'manage finance',
                'students.view', 'students.manage_students',
            ],
            $this->expensePermissions(),
        )));
    }

    private function teacherPermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                'teacher.dashboard', 'dashboard.view', 'dashboard.teacher.view',
                'students.view', 'students.manage_students',
                'attendance.view', 'attendance.create', 'attendance.mark', 'attendance.mark_attendance',
                'transport.view',
            ],
            $this->teacherAcademicPermissions(),
            $this->teacherNavPermissions(),
        )));
    }

    private function seniorTeacherPermissions(): array
    {
        return array_values(array_unique(array_merge(
            $this->teacherPermissions(),
            [
                'dashboard.senior_teacher.view',
                'schemes_of_work.approve', 'schemes_of_work.publish',
                'exams.publish', 'exams.approve', 'report_cards.publish', 'report_cards.generate',
                'homework.approve', 'homework.delete',
                'curriculum_designs.view', 'curriculum_designs.create', 'curriculum_designs.edit',
                'students.details.view',
            ],
            $this->seniorTeacherExtras(),
        )));
    }

    private function teacherAcademicPermissions(): array
    {
        return [
            'subjects.view', 'classrooms.view',
            'schemes_of_work.view', 'schemes_of_work.create', 'schemes_of_work.edit',
            'schemes_of_work.export_pdf', 'schemes_of_work.export_excel',
            'lesson_plans.view', 'lesson_plans.create', 'lesson_plans.edit',
            'lesson_plans.export_pdf', 'lesson_plans.export_excel',
            'exams.view', 'exams.create', 'exams.edit', 'exams.enter_marks', 'exams.import_marks', 'exams.export_marks', 'exams.calculate_grades',
            'portfolio_assessments.view', 'portfolio_assessments.create', 'portfolio_assessments.edit',
            'report_cards.view', 'report_cards.skills.edit', 'report_cards.remarks.edit', 'report_cards.competencies.edit', 'report_cards.export_pdf',
            'homework.view', 'homework.create', 'homework.edit', 'homework.assign', 'homework.mark', 'homework.view_diary',
            'cbc_strands.view', 'cbc_substrands.view', 'competencies.view', 'learning_areas.view',
            'curriculum_designs.view_own', 'curriculum_assistant.use',
            'exports.pdf', 'exports.excel',
            'academics.view', 'academics.manage', 'exams.manage', 'report_cards.manage',
            'extra_curricular.view', 'extra_curricular.create',
        ];
    }

    private function adminAcademicPermissions(): array
    {
        return [
            'subjects.view', 'subjects.create', 'subjects.edit', 'subjects.delete',
            'classrooms.view', 'classrooms.create', 'classrooms.edit', 'classrooms.delete',
            'schemes_of_work.view', 'schemes_of_work.create', 'schemes_of_work.edit', 'schemes_of_work.delete',
            'schemes_of_work.approve', 'schemes_of_work.publish', 'schemes_of_work.export_pdf', 'schemes_of_work.export_excel', 'schemes_of_work.generate',
            'lesson_plans.view', 'lesson_plans.create', 'lesson_plans.edit', 'lesson_plans.delete',
            'lesson_plans.export_pdf', 'lesson_plans.export_excel',
            'exams.view', 'exams.create', 'exams.edit', 'exams.delete', 'exams.publish',
            'exams.enter_marks', 'exams.import_marks', 'exams.export_marks', 'exams.approve', 'exams.calculate_grades',
            'exam_types.view', 'exam_types.create', 'exam_types.edit', 'exam_types.delete',
            'cbc_strands.view', 'cbc_strands.create', 'cbc_strands.edit', 'cbc_strands.delete', 'cbc_strands.manage',
            'cbc_substrands.view', 'cbc_substrands.create', 'cbc_substrands.edit', 'cbc_substrands.delete',
            'competencies.view', 'competencies.create', 'competencies.edit', 'competencies.delete',
            'learning_areas.view', 'learning_areas.create', 'learning_areas.edit', 'learning_areas.delete', 'learning_areas.manage',
            'portfolio_assessments.view', 'portfolio_assessments.create', 'portfolio_assessments.edit', 'portfolio_assessments.delete', 'portfolio_assessments.export_pdf',
            'report_cards.view', 'report_cards.create', 'report_cards.edit', 'report_cards.delete',
            'report_cards.publish', 'report_cards.generate', 'report_cards.export_pdf', 'report_cards.export_excel', 'report_cards.export_bulk',
            'report_cards.skills.edit', 'report_cards.remarks.edit', 'report_cards.competencies.edit',
            'homework.view', 'homework.create', 'homework.edit', 'homework.delete',
            'homework.assign', 'homework.mark', 'homework.view_diary', 'homework.approve',
            'exports.pdf', 'exports.excel', 'exports.bulk',
            'audit_logs.view', 'audit_logs.export',
            'curriculum_designs.view', 'curriculum_designs.create', 'curriculum_designs.edit', 'curriculum_designs.delete',
            'curriculum_assistant.use',
            'exams.manage', 'report_cards.manage',
        ];
    }

    private function parentPermissions(): array
    {
        return [
            'students.view',
            'communication.view',
            'homework.view_diary', 'report_cards.view', 'homework.view',
            'dashboard.view',
        ];
    }

    private function studentPermissions(): array
    {
        return [
            'student.dashboard', 'dashboard.view',
            'students.view',
            'homework.view', 'homework.submit', 'homework.view_diary', 'report_cards.view',
        ];
    }
}

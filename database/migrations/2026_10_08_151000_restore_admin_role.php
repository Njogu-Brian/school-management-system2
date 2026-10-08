<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->where('guard_name', 'web')->whereRaw('BINARY name = ?', ['Admin'])->first();

        if (! $role) {
            $role = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        }

        if ($role->permissions()->count() === 0) {
            $existing = \Spatie\Permission\Models\Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $this->permissions())
                ->pluck('name')
                ->all();
            $role->syncPermissions($existing);
        }

        $sharon = User::query()->where('email', 's.james@royalkingsschools.sc.ke')->first();
        if ($sharon && ! $sharon->hasRole('Admin')) {
            $sharon->assignRole($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The Admin role is required for the school office account.
    }

    /**
     * Permissions the production admin role held before the case-insensitive rename.
     *
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'academics.classrooms', 'academics.create', 'academics.delete', 'academics.edit', 'academics.manage',
            'academics.streams', 'academics.student_categories', 'academics.view', 'admin.dashboard',
            'admissions.create', 'admissions.delete', 'admissions.edit', 'admissions.online_admission', 'admissions.view',
            'attendance.create', 'attendance.delete', 'attendance.edit', 'attendance.mark', 'attendance.mark_attendance',
            'attendance.view', 'attendance.view_attendance', 'audit_logs.export', 'audit_logs.view',
            'cbc_strands.create', 'cbc_strands.delete', 'cbc_strands.edit', 'cbc_strands.manage', 'cbc_strands.view',
            'cbc_substrands.create', 'cbc_substrands.delete', 'cbc_substrands.edit', 'cbc_substrands.view',
            'classrooms.create', 'classrooms.delete', 'classrooms.edit', 'classrooms.view',
            'communication.announcements', 'communication.create', 'communication.delete', 'communication.edit',
            'communication.email_template', 'communication.email.add', 'communication.logs', 'communication.send_email',
            'communication.send_sms', 'communication.send_whatsapp', 'communication.sms_template', 'communication.sms.add',
            'communication.view', 'communication.whatsapp.add',
            'competencies.create', 'competencies.delete', 'competencies.edit', 'competencies.view',
            'curriculum_assistant.use', 'curriculum_designs.create', 'curriculum_designs.delete', 'curriculum_designs.edit',
            'curriculum_designs.view', 'curriculum_designs.view_own',
            'dashboard.senior_teacher.view', 'dashboard.teacher.view', 'dashboard.view',
            'diaries.create', 'diaries.delete', 'diaries.edit', 'diaries.view', 'events.manage',
            'exam_marks.create', 'exam_marks.view', 'exam_types.create', 'exam_types.delete', 'exam_types.edit', 'exam_types.view',
            'exams.approve', 'exams.calculate_grades', 'exams.create', 'exams.delete', 'exams.edit', 'exams.enter_marks',
            'exams.export_marks', 'exams.import_marks', 'exams.manage', 'exams.publish', 'exams.view',
            'expense.approve', 'expense.category.manage', 'expense.create', 'expense.pay', 'expense.report',
            'expense.submit', 'expense.view', 'exports.bulk', 'exports.excel', 'exports.pdf',
            'extra_curricular.create', 'extra_curricular.delete', 'extra_curricular.edit', 'extra_curricular.view',
            'finance.create', 'finance.delete', 'finance.edit', 'finance.fee_balances.view', 'finance.manage', 'finance.view',
            'homework.approve', 'homework.assign', 'homework.create', 'homework.delete', 'homework.edit', 'homework.mark',
            'homework.submit', 'homework.view', 'homework.view_diary', 'inventory.manage', 'inventory.view',
            'kitchen.daily_summary', 'learning_areas.create', 'learning_areas.delete', 'learning_areas.edit',
            'learning_areas.manage', 'learning_areas.view', 'lesson_plans.create', 'lesson_plans.delete',
            'lesson_plans.edit', 'lesson_plans.export_excel', 'lesson_plans.export_pdf', 'lesson_plans.view',
            'manage finance', 'manage settings', 'manage staff', 'manage students', 'manage transport',
            'portfolio_assessments.create', 'portfolio_assessments.delete', 'portfolio_assessments.edit',
            'portfolio_assessments.export_pdf', 'portfolio_assessments.view', 'report_card_skills.edit',
            'report_cards.competencies.edit', 'report_cards.create', 'report_cards.delete', 'report_cards.edit',
            'report_cards.export_bulk', 'report_cards.export_excel', 'report_cards.export_pdf', 'report_cards.generate',
            'report_cards.manage', 'report_cards.publish', 'report_cards.remarks.edit', 'report_cards.skills.edit',
            'report_cards.view', 'schemes_of_work.approve', 'schemes_of_work.create', 'schemes_of_work.delete',
            'schemes_of_work.edit', 'schemes_of_work.export_excel', 'schemes_of_work.export_pdf',
            'schemes_of_work.generate', 'schemes_of_work.publish', 'schemes_of_work.view',
            'senior_teacher.supervised_staff.view', 'senior_teacher.supervisory_classes.view',
            'settings.branding', 'settings.create', 'settings.delete', 'settings.edit', 'settings.general',
            'settings.manage', 'settings.regional', 'settings.roles_permissions', 'settings.view',
            'staff.create', 'staff.delete', 'staff.edit', 'staff.manage', 'staff.manage_staff', 'staff.upload_staff', 'staff.view',
            'student_behaviours.create', 'student_behaviours.delete', 'student_behaviours.edit', 'student_behaviours.view',
            'student_requirements.view', 'student.dashboard', 'students.create', 'students.delete', 'students.details.view',
            'students.edit', 'students.manage', 'students.manage_students', 'students.view',
            'subjects.create', 'subjects.delete', 'subjects.edit', 'subjects.view', 'teacher.dashboard',
            'timetable.edit', 'timetable.view', 'transport.create', 'transport.delete', 'transport.edit',
            'transport.index', 'transport.manage', 'transport.routes', 'transport.trips', 'transport.vehicles', 'transport.view',
            'vendor.manage', 'voucher.manage',
        ];
    }
};

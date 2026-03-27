<?php

namespace App\Support;

/**
 * Permissions class teachers should have (mirrors AcademicPermissionsSeeder + exam/attendance aliases).
 * Used by Gate::before when Spatie model_has_roles / role_has_permissions is out of sync with HR.
 */
final class TeacherAcademicPermissions
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = array_values(array_unique([
            // AcademicPermissionsSeeder — Teacher role
            'subjects.view',
            'classrooms.view',
            'schemes_of_work.view',
            'schemes_of_work.create',
            'schemes_of_work.edit',
            'schemes_of_work.export_pdf',
            'schemes_of_work.export_excel',
            'schemes_of_work.generate',
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
            // TeacherPermissionsSeeder / exam marks routes
            'exam_marks.view',
            'exam_marks.create',
            'dashboard.teacher.view',
            'diaries.view',
            'diaries.create',
            'diaries.edit',
            'student_behaviours.view',
            'student_behaviours.create',
            'student_behaviours.edit',
            // Attendance (nav + routes)
            'attendance.view',
            'attendance.create',
        ]));

        return $cache;
    }

    public static function allows(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }
}

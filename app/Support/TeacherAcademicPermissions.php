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

        $cache = array_values(array_unique(array_merge(
            [
                'subjects.view',
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
                'cbc_strands.view',
                'cbc_substrands.view',
                'competencies.view',
                'learning_areas.view',
                'curriculum_designs.view_own',
                'curriculum_assistant.use',
                'exports.pdf',
                'exports.excel',
                'student_behaviours.create',
                'student_behaviours.edit',
            ],
            self::homeroomViewPermissions(),
            self::subjectEditPermissions(),
        )));

        return $cache;
    }

    public static function allows(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }

    /**
     * Permissions granted to homeroom teachers (class + assistant) for pastoral duties.
     *
     * @return list<string>
     */
    public static function homeroomViewPermissions(): array
    {
        return [
            'attendance.view', 'attendance.create',
            'homework.view', 'homework.view_diary',
            'diaries.view', 'diaries.create', 'diaries.edit',
            'classrooms.view', 'dashboard.teacher.view',
            'exam_marks.view', 'exams.view',
            'report_cards.view', 'report_cards.export_pdf',
            'portfolio_assessments.view',
            'student_behaviours.view',
        ];
    }

    /**
     * Permissions for subject teachers who edit academic data for assigned subjects.
     *
     * @return list<string>
     */
    public static function subjectEditPermissions(): array
    {
        return [
            'exams.create', 'exams.edit', 'exams.enter_marks', 'exams.import_marks',
            'exams.export_marks', 'exams.calculate_grades',
            'homework.create', 'homework.edit', 'homework.assign', 'homework.mark',
            'report_cards.skills.edit', 'report_cards.remarks.edit', 'report_cards.competencies.edit',
            'portfolio_assessments.create', 'portfolio_assessments.edit',
            'exam_marks.create',
        ];
    }
}

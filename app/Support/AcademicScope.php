<?php

namespace App\Support;

use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Senior teachers oversee every class and subject in academic modules.
 * Regular teachers stay limited to the classes and subjects they teach.
 */
class AcademicScope
{
    public static function seesEveryClass(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Director', 'Academic Administrator'])) {
            return true;
        }

        return $user->isSeniorTeacherUser();
    }

    /**
     * @return Collection<int, Classroom>
     */
    public static function classrooms(?User $user): Collection
    {
        if (! $user || self::seesEveryClass($user)) {
            return Classroom::query()->orderBy('name')->get();
        }

        $ids = $user->isDeputySeniorTeacherUser()
            ? $user->getDashboardClassroomIds()
            : array_map('intval', $user->getAssignedClassroomIds());

        if ($ids === []) {
            return collect();
        }

        return Classroom::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    public static function allowsClassroom(?User $user, int|string|null $classroomId): bool
    {
        if ($classroomId === null || $classroomId === '') {
            return true;
        }

        if (! $user || self::seesEveryClass($user)) {
            return true;
        }

        $ids = self::classrooms($user)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return in_array((int) $classroomId, $ids, true);
    }

    /**
     * @return Collection<int, Subject>
     */
    public static function subjects(?User $user): Collection
    {
        if (! $user || self::seesEveryClass($user)) {
            return Subject::active()->orderBy('name')->get();
        }

        $ids = $user->getAssignedSubjectIds();
        if ($ids === []) {
            return collect();
        }

        return Subject::query()->whereIn('id', $ids)->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Module and submodule permissions for exams, report cards, lesson plans,
     * diaries, homework, CBC curriculum, and timetable.
     *
     * @return list<string>
     */
    public static function seniorTeacherModulePermissions(): array
    {
        return [
            'exams.view', 'exams.create', 'exams.edit', 'exams.delete', 'exams.publish',
            'exams.enter_marks', 'exams.import_marks', 'exams.export_marks', 'exams.approve', 'exams.calculate_grades',
            'exams.manage',
            'exam_types.view', 'exam_types.create', 'exam_types.edit', 'exam_types.delete',
            'exam_marks.view', 'exam_marks.create',
            'report_cards.view', 'report_cards.create', 'report_cards.edit', 'report_cards.delete',
            'report_cards.publish', 'report_cards.generate',
            'report_cards.export_pdf', 'report_cards.export_excel', 'report_cards.export_bulk',
            'report_cards.skills.edit', 'report_cards.remarks.edit', 'report_cards.competencies.edit',
            'report_cards.manage',
            'report_card_skills.edit',
            'homework.view', 'homework.create', 'homework.edit', 'homework.delete',
            'homework.assign', 'homework.mark', 'homework.view_diary', 'homework.approve', 'homework.submit',
            'diaries.view', 'diaries.create', 'diaries.edit', 'diaries.delete',
            'lesson_plans.view', 'lesson_plans.create', 'lesson_plans.edit', 'lesson_plans.delete',
            'lesson_plans.export_pdf', 'lesson_plans.export_excel',
            'schemes_of_work.view', 'schemes_of_work.create', 'schemes_of_work.edit', 'schemes_of_work.delete',
            'schemes_of_work.approve', 'schemes_of_work.publish',
            'schemes_of_work.export_pdf', 'schemes_of_work.export_excel', 'schemes_of_work.generate',
            'cbc.view', 'cbc.create', 'cbc.edit', 'cbc.delete',
            'cbc_strands.view', 'cbc_strands.create', 'cbc_strands.edit', 'cbc_strands.delete', 'cbc_strands.manage',
            'cbc_substrands.view', 'cbc_substrands.create', 'cbc_substrands.edit', 'cbc_substrands.delete',
            'competencies.view', 'competencies.create', 'competencies.edit', 'competencies.delete',
            'learning_areas.view', 'learning_areas.create', 'learning_areas.edit', 'learning_areas.delete', 'learning_areas.manage',
            'curriculum_designs.view', 'curriculum_designs.view_own', 'curriculum_designs.create', 'curriculum_designs.edit', 'curriculum_designs.delete',
            'curriculum_assistant.use',
            'portfolio_assessments.view', 'portfolio_assessments.create', 'portfolio_assessments.edit',
            'portfolio_assessments.delete', 'portfolio_assessments.export_pdf',
            'timetable.view', 'timetable.edit',
            'academics.view', 'academics.manage',
        ];
    }
}

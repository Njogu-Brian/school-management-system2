<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Scopes class-teacher vs subject-teacher access for academic and pastoral features.
 */
final class TeacherClassAccess
{
    /**
     * Class teacher or assistant: attendance, transport, bio-data, diary, view homework.
     */
    public static function isHomeroomFor(User $user, int $classroomId, ?int $streamId = null): bool
    {
        if (! $user->staff) {
            return false;
        }

        $staffId = $user->staff->id;

        $classTeacher = DB::table('class_teacher_assignments')
            ->where('staff_id', $staffId)
            ->where('classroom_id', $classroomId)
            ->when(
                $streamId === null,
                fn ($q) => $q->whereNull('stream_id'),
                fn ($q) => $q->where('stream_id', $streamId)
            )
            ->exists();

        if ($classTeacher) {
            return true;
        }

        return DB::table('assistant_class_teacher_assignments')
            ->where('staff_id', $staffId)
            ->where('classroom_id', $classroomId)
            ->when(
                $streamId === null,
                fn ($q) => $q->whereNull('stream_id'),
                fn ($q) => $q->where('stream_id', $streamId)
            )
            ->exists();
    }

    /**
     * Subject teacher may edit academic data only for assigned subject slots.
     */
    public static function teachesSubjectInClass(
        User $user,
        int $classroomId,
        int $subjectId,
        ?int $streamId = null,
    ): bool {
        if (! $user->staff) {
            return false;
        }

        $q = DB::table('classroom_subjects')
            ->where('staff_id', $user->staff->id)
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId);

        if ($streamId === null) {
            $q->whereNull('stream_id');
        } else {
            $q->where('stream_id', $streamId);
        }

        return $q->exists();
    }

    /**
     * View academic data: homeroom (any subject) or teaches at least one subject in the class.
     */
    public static function canViewAcademicData(User $user, int $classroomId, ?int $streamId = null): bool
    {
        if (self::isHomeroomFor($user, $classroomId, $streamId)) {
            return true;
        }

        if (! $user->staff) {
            return false;
        }

        $q = DB::table('classroom_subjects')
            ->where('staff_id', $user->staff->id)
            ->where('classroom_id', $classroomId);

        if ($streamId !== null) {
            $q->where('stream_id', $streamId);
        }

        return $q->exists();
    }

    /**
     * Edit academic data (marks, homework, report comments): subject teacher only.
     */
    public static function canEditAcademicData(
        User $user,
        int $classroomId,
        int $subjectId,
        ?int $streamId = null,
    ): bool {
        return self::teachesSubjectInClass($user, $classroomId, $subjectId, $streamId);
    }

    /**
     * Homeroom pastoral actions: attendance, transport, diary control.
     */
    public static function canManagePastoral(User $user, int $classroomId, ?int $streamId = null): bool
    {
        return self::isHomeroomFor($user, $classroomId, $streamId);
    }
}

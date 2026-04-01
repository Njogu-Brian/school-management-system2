<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class ExamReportsAccess
{
    /**
     * Full school-wide exam analytics (all classes, school-wide teacher rankings, term workbooks for all classes).
     */
    public static function userHasFullAccess(?User $user): bool
    {
        return $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
    }

    /**
     * Classrooms the user may run exam reports for (assigned + supervised campus for senior teachers).
     *
     * @return Builder<Classroom>
     */
    public static function classroomsQueryFor(?User $user): Builder
    {
        $q = Classroom::query()->orderBy('name');

        if ($user && ! self::userHasFullAccess($user)) {
            $ids = $user->getDashboardClassroomIds();
            if ($ids === []) {
                return $q->whereRaw('1 = 0');
            }

            return $q->whereIn('id', $ids);
        }

        return $q;
    }

    /**
     * @return int[]
     */
    public static function allowedClassroomIdsFor(?User $user): array
    {
        if (! $user || self::userHasFullAccess($user)) {
            return [];
        }

        return $user->getDashboardClassroomIds();
    }

    public static function assertClassroomAccess(?User $user, int $classroomId): void
    {
        if (! $user) {
            abort(403, 'Authentication required.');
        }
        if (self::userHasFullAccess($user)) {
            return;
        }
        if (! $user->canTeacherAccessClassroom($classroomId)) {
            abort(403, 'You do not have access to this class.');
        }
    }

    public static function assertSchoolWideReportsAllowed(?User $user): void
    {
        if (! self::userHasFullAccess($user)) {
            abort(403, 'School-wide reports are only available to administrators.');
        }
    }

    /**
     * Non-admins must scope trends/insights to a class they can access.
     */
    public static function assertTrendsClassroomScope(?User $user, ?int $classroomId): void
    {
        if ($user && self::userHasFullAccess($user)) {
            return;
        }
        if (! $classroomId) {
            abort(403, 'Select a class for this report.');
        }
        self::assertClassroomAccess($user, $classroomId);
    }

    public static function currentUser(): ?User
    {
        $u = Auth::user();

        return $u instanceof User ? $u : null;
    }
}

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Academics\Exam;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Let high-privilege roles (Super Admin, Admin) bypass all checks
        // Admins can enter marks for all exams and all students
        Gate::before(function (User $user, string $ability = null) {
            return $user->hasAnyRole(['Super Admin','Admin','System Admin']) ? true : null;
        });

        // Teachers can only enter marks for classes/subjects assigned to them
        Gate::define('enter-marks', function (User $user, Exam $exam, int $classroomId, int $subjectId) {
            // Admins are already handled by Gate::before above
            $staff = $user->staff ?? null;
            if (!$staff) return false;

            // Check if teacher is assigned to teach this subject in this classroom
            $q = DB::table('classroom_subjects')
                ->where('staff_id', $staff->id)
                ->where('classroom_id', $classroomId)
                ->where('subject_id', $subjectId);

            if ($exam->academic_year_id) {
                $q->where(function($q2) use ($exam){
                    $q2->whereNull('academic_year_id')->orWhere('academic_year_id', $exam->academic_year_id);
                });
            }
            if ($exam->term_id) {
                $q->where(function($q2) use ($exam){
                    $q2->whereNull('term_id')->orWhere('term_id', $exam->term_id);
                });
            }

            return $q->exists();
        });

        Gate::define('view-marks', function (User $user, int $classroomId) {
            $staff = $user->staff ?? null;
            if (!$staff) return false;

            $teachesInClass = DB::table('classroom_subjects')
                ->where('staff_id', $staff->id)
                ->where('classroom_id', $classroomId)
                ->exists();

            $isClassTeacher = DB::table('classroom_teacher')
                ->where('teacher_id', $user->id) // users.id
                ->where('classroom_id', $classroomId)
                ->exists();

            return $teachesInClass || $isClassTeacher;
        });
    }
}

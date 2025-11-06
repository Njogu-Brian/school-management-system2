<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB; // <— add
use App\Models\User;               // <— add
use App\Models\Academics\Exam;     // <— add

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability) {
            return method_exists($user,'hasRole') && $user->hasRole('super admin') ? true : null;
        });

        Gate::define('enter-marks', function (User $user, Exam $exam, int $classroomId, int $subjectId) {
            if (method_exists($user,'hasRole') && $user->hasRole('admin')) return true;
            if (method_exists($user,'can') && $user->can('exam.manage')) return true;

            $staff = $user->staff ?? null;
            if (!$staff) return false;

            $q = DB::table('classroom_subjects')
                ->where('staff_id', $staff->id)
                ->where('classroom_id', $classroomId)
                ->where('subject_id', $subjectId);

            if ($exam->academic_year_id) {
                $q->where(function($q2) use ($exam){
                    $q2->whereNull('academic_year_id')->orWhere('academic_year_id',$exam->academic_year_id);
                });
            }
            if ($exam->term_id) {
                $q->where(function($q2) use ($exam){
                    $q2->whereNull('term_id')->orWhere('term_id',$exam->term_id);
                });
            }

            return $q->exists();
        });

        Gate::define('view-marks', function (User $user, int $classroomId) {
            if (method_exists($user,'hasRole') && $user->hasRole('admin')) return true;

            $staff = $user->staff ?? null;
            if (!$staff) return false;

            $teachesInClass = DB::table('classroom_subjects')
                ->where('staff_id',$staff->id)
                ->where('classroom_id',$classroomId)
                ->exists();

            $isClassTeacher = DB::table('classroom_teacher')
                ->where('teacher_id',$user->id)   // points to users.id
                ->where('classroom_id',$classroomId)
                ->exists();

            return $teachesInClass || $isClassTeacher;
        });
    }
}

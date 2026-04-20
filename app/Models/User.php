<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ParentInfo;
use App\Models\Student;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;

class User extends Authenticatable implements WebAuthnAuthenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;
    use \App\Models\Concerns\NormalizesNameAttributes;
    use WebAuthnAuthentication;

    protected static array $sentenceCaseNameAttributes = [
        'name',
    ];

    protected $fillable = [
        'name', 'email', 'password', 'must_change_password',
        'google_id', 'google_email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Spatie role names may differ in casing across seeders and guards.
     */
    public static function teacherLikeRoleNames(): array
    {
        return ['Teacher', 'Senior Teacher', 'Supervisor', 'teacher', 'senior teacher', 'supervisor'];
    }

    public function hasTeacherLikeRole(): bool
    {
        return $this->hasAnyRole(self::teacherLikeRoleNames());
    }

    /**
     * HR may assign teaching duties via classroom/subject/stream pivots while Spatie role names differ.
     */
    public function hasTeachingAssignments(): bool
    {
        if (! $this->staff) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('classroom_teacher')->where('teacher_id', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('classroom_subjects')->where('staff_id', $this->staff->id)->exists()
            || \Illuminate\Support\Facades\DB::table('stream_teacher')->where('teacher_id', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('subject_teacher')->where('teacher_id', $this->id)->exists();
    }

    public function isSeniorTeacherUser(): bool
    {
        return $this->hasAnyRole(['Senior Teacher', 'senior teacher', 'Senior teacher']);
    }

    /**
     * Classrooms for dashboards, KPIs, and exam scope (assigned + supervised campus for senior teachers).
     *
     * @return int[]
     */
    public function getDashboardClassroomIds(): array
    {
        $ids = array_map('intval', $this->getAssignedClassroomIds());
        if ($this->isSeniorTeacherUser()) {
            $ids = array_values(array_unique(array_merge(
                $ids,
                array_map('intval', $this->getSupervisedClassroomIds())
            )));
        }
        sort($ids);

        return $ids;
    }

    /**
     * Whether this user may view or mark data for a classroom (API parity with web attendance).
     */
    public function canTeacherAccessClassroom(int $classroomId): bool
    {
        if (! $this->hasTeacherLikeRole()) {
            return false;
        }
        $cid = (int) $classroomId;
        $assigned = array_map('intval', $this->getAssignedClassroomIds());
        if (in_array($cid, $assigned, true)) {
            return true;
        }
        if ($this->isSeniorTeacherUser()) {
            return in_array($cid, array_map('intval', $this->getSupervisedClassroomIds()), true);
        }

        return false;
    }

    public function subjects()
    {
        return $this->belongsToMany(\App\Models\Academics\Subject::class, 'subject_teacher', 'teacher_id', 'subject_id');
    }

    public function classrooms()
    {
        return $this->belongsToMany(\App\Models\Academics\Classroom::class, 'classroom_teacher', 'teacher_id', 'classroom_id');
    }

    public function streams()
    {
        return $this->belongsToMany(\App\Models\Academics\Stream::class, 'stream_teacher', 'teacher_id', 'stream_id');
    }

    /**
     * Senior Teacher: campus assignment (one campus per senior teacher).
     * Supervisory scope is derived solely from this campus.
     */
    public function campusAssignment()
    {
        return $this->hasOne(\App\Models\CampusSeniorTeacher::class, 'senior_teacher_id');
    }

    /**
     * Check if this user is a senior teacher supervising a specific classroom
     * (classroom must belong to the senior teacher's assigned campus).
     */
    public function isSupervisingClassroom($classroomId): bool
    {
        $ids = $this->getSupervisedClassroomIds();
        return in_array((int) $classroomId, $ids, true);
    }

    /**
     * Check if this user is a senior teacher supervising a specific staff member
     * (staff must be assigned to at least one classroom in the senior teacher's campus).
     */
    public function isSupervisingStaff($staffId): bool
    {
        $ids = $this->getSupervisedStaffIds();
        return in_array((int) $staffId, $ids, true);
    }

    /**
     * Get all classroom IDs supervised by this senior teacher (from assigned campus only).
     */
    public function getSupervisedClassroomIds(): array
    {
        $assignment = $this->campusAssignment;
        if (!$assignment || ! $this->isSeniorTeacherUser()) {
            return [];
        }
        return \App\Models\Academics\Classroom::forCampus($assignment->campus)->pluck('id')->toArray();
    }

    /**
     * Get all staff IDs supervised by this senior teacher:
     * - Staff in assigned campus classrooms (classroom_subjects + classroom_teacher)
     * - Staff explicitly assigned "supervised by" this senior teacher (supervisor_id).
     */
    public function getSupervisedStaffIds(): array
    {
        $ids = [];

        $classroomIds = $this->getSupervisedClassroomIds();
        if (!empty($classroomIds)) {
            $fromSubjectAssignments = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                ->whereIn('classroom_id', $classroomIds)
                ->distinct()
                ->pluck('staff_id')
                ->toArray();
            $teacherUserIds = \Illuminate\Support\Facades\DB::table('classroom_teacher')
                ->whereIn('classroom_id', $classroomIds)
                ->distinct()
                ->pluck('teacher_id')
                ->toArray();
            $fromTeachers = \App\Models\Staff::whereIn('user_id', $teacherUserIds)->pluck('id')->toArray();
            $ids = array_merge($ids, $fromSubjectAssignments, $fromTeachers);
        }

        // Include staff who have this senior teacher as supervisor ("supervised by" assignment)
        if ($this->staff) {
            $subordinateIds = \App\Models\Staff::where('supervisor_id', $this->staff->id)->pluck('id')->toArray();
            $ids = array_merge($ids, $subordinateIds);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Get all students in supervised classrooms (for senior teachers; scope = assigned campus).
     */
    public function getSupervisedStudents()
    {
        $classroomIds = $this->getSupervisedClassroomIds();
        if (empty($classroomIds)) {
            return Student::whereRaw('1 = 0');
        }
        return Student::whereIn('classroom_id', $classroomIds);
    }

    /**
     * Check if teacher is assigned to a classroom
     */
    public function isAssignedToClassroom($classroomId): bool
    {
        return $this->classrooms()->where('classrooms.id', $classroomId)->exists();
    }

    /**
     * Check if teacher is assigned to a stream
     */
    public function isAssignedToStream($streamId): bool
    {
        return $this->streams()->where('streams.id', $streamId)->exists();
    }

    /**
     * Get all classroom IDs assigned to this teacher
     * Includes direct assignments (classroom_teacher), subject assignments (classroom_subjects), and stream assignments (stream_teacher)
     */
    public function getAssignedClassroomIds(): array
    {
        // Get from direct classroom_teacher assignments
        $directClassroomIds = $this->classrooms()->pluck('classrooms.id')->toArray();
        
        // Get from classroom_subjects via staff
        $subjectClassroomIds = [];
        if ($this->staff) {
            $subjectClassroomIds = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                ->where('staff_id', $this->staff->id)
                ->distinct()
                ->pluck('classroom_id')
                ->toArray();
        }
        
        // Get from stream_teacher assignments (streams are assigned to specific classrooms)
        // Only use the classroom_id from stream_teacher pivot, not the stream's primary classroom_id
        // This ensures we only get the exact classroom where the teacher is assigned to the stream
        $streamClassroomIds = \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->where('teacher_id', $this->id)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->pluck('classroom_id')
            ->toArray();

        $pivotNullStreamIds = \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->where('teacher_id', $this->id)
            ->whereNull('classroom_id')
            ->pluck('stream_id')
            ->toArray();
        $streamFallbackClassroomIds = [];
        if (! empty($pivotNullStreamIds)) {
            $streamFallbackClassroomIds = \App\Models\Academics\Stream::whereIn('id', $pivotNullStreamIds)
                ->whereNotNull('classroom_id')
                ->pluck('classroom_id')
                ->toArray();
        }

        // Merge and return unique IDs
        return array_values(array_unique(array_merge(
            $directClassroomIds,
            $subjectClassroomIds,
            $streamClassroomIds,
            $streamFallbackClassroomIds
        )));
    }

    /**
     * Get all stream IDs assigned to this teacher
     */
    public function getAssignedStreamIds(): array
    {
        return $this->streams()->pluck('streams.id')->toArray();
    }

    /**
     * Get all stream IDs in supervised classrooms (for Senior Teachers).
     * Senior teachers supervise by campus, so they see all streams in that campus's classrooms.
     */
    public function getSupervisedStreamIds(): array
    {
        $classroomIds = $this->getSupervisedClassroomIds();
        if (empty($classroomIds)) {
            return [];
        }
        return \App\Models\Academics\Stream::whereIn('classroom_id', $classroomIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get effective stream IDs for teachers: assigned streams + supervised streams (for Senior Teachers).
     * Used in attendance, students list, etc. so senior teachers can see and filter by all streams in their scope.
     */
    public function getEffectiveStreamIds(): array
    {
        $assigned = $this->getAssignedStreamIds();
        if ($this->isSeniorTeacherUser()) {
            $supervised = $this->getSupervisedStreamIds();
            return array_values(array_unique(array_merge($assigned, $supervised)));
        }
        return $assigned;
    }

    /**
     * Check if this user supervises a stream (stream belongs to a supervised classroom).
     */
    public function isSupervisingStream($streamId): bool
    {
        $streamIds = $this->getSupervisedStreamIds();
        return in_array((int) $streamId, $streamIds, true);
    }

    /**
     * Get stream assignments with classroom_id and stream_id
     * Returns array of objects with classroom_id and stream_id
     */
    public function getStreamAssignments(): array
    {
        $rows = \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->where('teacher_id', $this->id)
            ->get(['classroom_id', 'stream_id']);

        $out = [];
        $needClassroomByStream = [];
        foreach ($rows as $item) {
            if ($item->classroom_id !== null) {
                $out[] = (object) [
                    'classroom_id' => (int) $item->classroom_id,
                    'stream_id' => (int) $item->stream_id,
                ];
            } else {
                $needClassroomByStream[] = (int) $item->stream_id;
            }
        }
        if (! empty($needClassroomByStream)) {
            $resolved = \App\Models\Academics\Stream::whereIn('id', $needClassroomByStream)
                ->whereNotNull('classroom_id')
                ->get(['id', 'classroom_id']);
            foreach ($resolved as $s) {
                $out[] = (object) [
                    'classroom_id' => (int) $s->classroom_id,
                    'stream_id' => (int) $s->id,
                ];
            }
        }

        return $out;
    }

    /**
     * Apply teacher-specific student filtering to a query
     * This ensures teachers only see students from their assigned streams/classrooms.
     * Senior teachers additionally see all students in classrooms on their supervised campus (portal parity).
     */
    public function applyTeacherStudentFilter($query, $streamAssignments = null, $assignedClassroomIds = null)
    {
        if ($streamAssignments === null) {
            $streamAssignments = $this->getStreamAssignments();
        }
        if ($assignedClassroomIds === null) {
            $assignedClassroomIds = $this->getAssignedClassroomIds();
        }

        $supervisedClassroomIds = $this->isSeniorTeacherUser() ? $this->getSupervisedClassroomIds() : [];

        $query->where(function ($outer) use ($streamAssignments, $assignedClassroomIds, $supervisedClassroomIds) {
            $matchedAny = false;

            if (! empty($streamAssignments)) {
                $outer->where(function ($q) use ($streamAssignments) {
                    foreach ($streamAssignments as $assignment) {
                        $q->orWhere(function ($subQ) use ($assignment) {
                            $subQ->where('classroom_id', $assignment->classroom_id)
                                ->where('stream_id', $assignment->stream_id);
                        });
                    }

                    $directClassroomIds = \DB::table('classroom_teacher')
                        ->where('teacher_id', $this->id)
                        ->pluck('classroom_id')
                        ->toArray();

                    $subjectClassroomIds = [];
                    if ($this->staff) {
                        $subjectClassroomIds = \DB::table('classroom_subjects')
                            ->where('staff_id', $this->staff->id)
                            ->distinct()
                            ->pluck('classroom_id')
                            ->toArray();
                    }

                    $streamClassroomIds = array_column($streamAssignments, 'classroom_id');
                    $nonStreamClassroomIds = array_diff(
                        array_unique(array_merge($directClassroomIds, $subjectClassroomIds)),
                        $streamClassroomIds
                    );

                    if (! empty($nonStreamClassroomIds)) {
                        $q->orWhereIn('classroom_id', $nonStreamClassroomIds);
                    }
                });
                $matchedAny = true;
            } elseif (! empty($assignedClassroomIds)) {
                $outer->whereIn('classroom_id', $assignedClassroomIds);
                $matchedAny = true;
            }

            if (! empty($supervisedClassroomIds)) {
                $outer->orWhereIn('classroom_id', $supervisedClassroomIds);
                $matchedAny = true;
            }

            if (! $matchedAny) {
                $outer->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Get the staff record associated with this user
     */
    public function staff()
    {
        return $this->hasOne(\App\Models\Staff::class, 'user_id');
    }

    public function parentProfile()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function children()
    {
        $relation = $this->hasMany(Student::class, 'parent_id', 'parent_id');

        if (is_null($this->parent_id)) {
            $relation->whereRaw('1 = 0');
        }

        return $relation;
    }

    /**
     * Student IDs a parent/guardian user may access (direct + siblings via family_id).
     *
     * @return list<int>
     */
    public function accessibleStudentIds(): array
    {
        if (! $this->parent_id) {
            return [];
        }

        $directIds = Student::where('parent_id', $this->parent_id)->pluck('id');
        $familyIds = Student::where('parent_id', $this->parent_id)->whereNotNull('family_id')->pluck('family_id')->unique()->filter();

        if ($familyIds->isEmpty()) {
            return $directIds->unique()->values()->all();
        }

        $siblingIds = Student::whereIn('family_id', $familyIds)->pluck('id');

        return $directIds->merge($siblingIds)->unique()->values()->all();
    }

    public function canAccessStudent(int $studentId): bool
    {
        return in_array($studentId, $this->accessibleStudentIds(), true);
    }
}

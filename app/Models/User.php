<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ParentInfo;
use App\Models\Student;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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
        if (!$assignment || !$this->hasRole('Senior Teacher')) {
            return [];
        }
        return \App\Models\Academics\Classroom::forCampus($assignment->campus)->pluck('id')->toArray();
    }

    /**
     * Get all staff IDs supervised by this senior teacher (staff in assigned campus classrooms).
     */
    public function getSupervisedStaffIds(): array
    {
        $classroomIds = $this->getSupervisedClassroomIds();
        if (empty($classroomIds)) {
            return [];
        }
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
        return array_values(array_unique(array_merge($fromSubjectAssignments, $fromTeachers)));
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
        
        // Merge and return unique IDs
        return array_unique(array_merge($directClassroomIds, $subjectClassroomIds, $streamClassroomIds));
    }

    /**
     * Get all stream IDs assigned to this teacher
     */
    public function getAssignedStreamIds(): array
    {
        return $this->streams()->pluck('streams.id')->toArray();
    }

    /**
     * Get stream assignments with classroom_id and stream_id
     * Returns array of objects with classroom_id and stream_id
     */
    public function getStreamAssignments(): array
    {
        return \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->where('teacher_id', $this->id)
            ->whereNotNull('classroom_id')
            ->select('classroom_id', 'stream_id')
            ->get()
            ->map(function($item) {
                return (object)[
                    'classroom_id' => $item->classroom_id,
                    'stream_id' => $item->stream_id,
                ];
            })
            ->toArray();
    }

    /**
     * Apply teacher-specific student filtering to a query
     * This ensures teachers only see students from their assigned streams/classrooms
     */
    public function applyTeacherStudentFilter($query, $streamAssignments = null, $assignedClassroomIds = null)
    {
        if ($streamAssignments === null) {
            $streamAssignments = $this->getStreamAssignments();
        }
        if ($assignedClassroomIds === null) {
            $assignedClassroomIds = $this->getAssignedClassroomIds();
        }

        // If teacher has stream assignments, filter by those specific streams
        if (!empty($streamAssignments)) {
            $query->where(function($q) use ($streamAssignments, $assignedClassroomIds) {
                // Students from assigned streams
                foreach ($streamAssignments as $assignment) {
                    $q->orWhere(function($subQ) use ($assignment) {
                        $subQ->where('classroom_id', $assignment->classroom_id)
                             ->where('stream_id', $assignment->stream_id);
                    });
                }
                
                // Also include students from direct classroom assignments (not via streams)
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
                
                if (!empty($nonStreamClassroomIds)) {
                    $q->orWhereIn('classroom_id', $nonStreamClassroomIds);
                }
            });
        } else {
            // No stream assignments, show all students from assigned classrooms
            if (!empty($assignedClassroomIds)) {
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }
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
}

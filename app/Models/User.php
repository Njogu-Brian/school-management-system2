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
        $streamClassroomIds = \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->where('teacher_id', $this->id)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->pluck('classroom_id')
            ->toArray();
        
        // Also get classrooms from streams that have a primary classroom_id
        $streamIds = $this->streams()->pluck('streams.id')->toArray();
        if (!empty($streamIds)) {
            $primaryStreamClassroomIds = \Illuminate\Support\Facades\DB::table('streams')
                ->whereIn('id', $streamIds)
                ->whereNotNull('classroom_id')
                ->distinct()
                ->pluck('classroom_id')
                ->toArray();
            $streamClassroomIds = array_merge($streamClassroomIds, $primaryStreamClassroomIds);
        }
        
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

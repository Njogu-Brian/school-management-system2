<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Student;   // ✅ import Student from App\Models
use App\Models\User;      // ✅ import User from App\Models

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'campus',
        'academic_group',
        'level',
        'next_class_id',
        'is_beginner',
        'is_alumni',
        'level_type',
    ];

    protected $casts = [
        'is_beginner' => 'boolean',
        'is_alumni' => 'boolean',
    ];

    public function nextClass()
    {
        return $this->belongsTo(Classroom::class, 'next_class_id');
    }

    public function previousClasses()
    {
        return $this->hasMany(Classroom::class, 'next_class_id');
    }

    /**
     * Primary streams (streams where this is the primary classroom)
     */
    public function primaryStreams()
    {
        return $this->hasMany(Stream::class, 'classroom_id');
    }

    /**
     * All streams assigned to this classroom (primary + via pivot)
     */
    public function streams()
    {
        return $this->belongsToMany(Stream::class, 'classroom_stream')
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * Get all streams (primary + additional) for this classroom
     */
    public function allStreams()
    {
        $primary = $this->primaryStreams;
        $additional = $this->streams;
        
        $all = $primary->merge($additional)->unique('id');
        
        return $all;
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'classroom_teacher', 'classroom_id', 'teacher_id');
    }

    /**
     * Get all teachers assigned to this classroom (direct + via streams)
     */
    public function allTeachers()
    {
        // Get direct teachers
        $directTeachers = $this->teachers;
        
        // Get teachers assigned via streams in this classroom
        $streamTeacherIds = \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->join('streams', 'stream_teacher.stream_id', '=', 'streams.id')
            ->where('stream_teacher.classroom_id', $this->id)
            ->distinct()
            ->pluck('stream_teacher.teacher_id')
            ->toArray();
        
        // Also check streams that have this classroom as primary and get their teachers
        $primaryStreamTeacherIds = \Illuminate\Support\Facades\DB::table('stream_teacher')
            ->join('streams', 'stream_teacher.stream_id', '=', 'streams.id')
            ->where('streams.classroom_id', $this->id)
            ->whereNull('stream_teacher.classroom_id') // If classroom_id is null in pivot, use stream's primary classroom
            ->distinct()
            ->pluck('stream_teacher.teacher_id')
            ->toArray();
        
        $allTeacherIds = array_unique(array_merge(
            $directTeachers->pluck('id')->toArray(),
            $streamTeacherIds,
            $primaryStreamTeacherIds
        ));
        
        return User::whereIn('id', $allTeacherIds)->get();
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'classroom_subjects');
    }
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
    public function subjectAssignments() // rows in classroom_subjects
    {
        return $this->hasMany(\App\Models\Academics\ClassroomSubject::class, 'classroom_id');
    }

    public function subjectTeachers() // teachers (staff) through assignments
    {
        return $this->belongsToMany(
            \App\Models\Staff::class,
            'classroom_subjects',
            'classroom_id',
            'staff_id'
        )->withPivot(['subject_id','stream_id','academic_year_id','term_id','is_compulsory']);
    }

    /**
     * Scope: classrooms that belong to the given campus (lower/upper).
     * Uses campus column and level_type fallback. Matches admin form labels:
     * Lower Campus = Grade 4-9 (upper_primary, junior_high); Upper Campus = Creche–Grade 3 (preschool, lower_primary).
     */
    public function scopeForCampus($query, string $campus)
    {
        $campus = strtolower($campus);
        return $query->where(function ($q) use ($campus) {
            $q->where('campus', $campus);
            if ($campus === 'lower') {
                $q->orWhereIn('level_type', ['upper_primary', 'junior_high']);
            } else {
                $q->orWhereIn('level_type', ['preschool', 'lower_primary']);
            }
        });
    }
}

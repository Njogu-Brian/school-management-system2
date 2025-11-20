<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Student;   // ✅ import Student from App\Models
use App\Models\User;      // ✅ import User from App\Models

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'next_class_id', 'is_beginner', 'is_alumni'];

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
     * Each classroom has many streams
     * Streams are unique per classroom (stream "A" in Classroom 1 is different from stream "A" in Classroom 2)
     */
    public function streams()
    {
        return $this->hasMany(Stream::class, 'classroom_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'classroom_teacher', 'classroom_id', 'teacher_id');
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

}

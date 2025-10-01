<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\Term;
use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;

class Exam extends Model
{
    protected $fillable = [
        'name',
        'type',
        'modality',
        'academic_year_id',
        'term_id',
        'created_by',
        'starts_on',
        'ends_on',
        'max_marks',
        'weight',
        'status',
        'published_at',
        'locked_at',
        'settings'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'locked_at'    => 'datetime',
        'settings'     => 'array',
        'starts_on'    => 'datetime',
        'ends_on'      => 'datetime',
    ];

// Many-to-Many with classrooms
public function classrooms()
{
    return $this->belongsToMany(Classroom::class, 'exam_class_subject')
        ->withPivot('subject_id')
        ->withTimestamps();
}

// Many-to-Many with subjects
public function subjects()
{
    return $this->belongsToMany(Subject::class, 'exam_class_subject')
        ->withPivot('classroom_id')
        ->withTimestamps();
}


    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    public function papers()
    {
        return $this->hasMany(ExamPaper::class);
    }

    
}

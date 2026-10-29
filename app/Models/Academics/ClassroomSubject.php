<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ClassroomSubject extends Model
{
    protected $table = 'classroom_subjects';

    protected $fillable = [
        'classroom_id',
        'stream_id',
        'subject_id',
        'staff_id',
        'academic_year_id',
        'term_id',
        'is_compulsory',
        'lessons_per_week'
    ];

    protected $casts = [
        'lessons_per_week' => 'integer',
        'is_compulsory' => 'boolean',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'staff_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(\App\Models\Term::class);
    }
}

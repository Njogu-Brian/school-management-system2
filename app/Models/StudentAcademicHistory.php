<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAcademicHistory extends Model
{
    protected $table = 'student_academic_history';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'classroom_id',
        'stream_id',
        'enrollment_date',
        'completion_date',
        'promotion_status',
        'final_grade',
        'class_position',
        'stream_position',
        'remarks',
        'teacher_comments',
        'is_current',
        'promoted_by',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'final_grade' => 'decimal:2',
        'is_current' => 'boolean',
        'class_position' => 'integer',
        'stream_position' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }

    public function stream()
    {
        return $this->belongsTo(\App\Models\Academics\Stream::class);
    }

    public function promotedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'promoted_by');
    }
}

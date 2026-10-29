<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $fillable = [
        'classroom_id',
        'academic_year_id',
        'term_id',
        'day',
        'period',
        'start_time',
        'end_time',
        'subject_id',
        'staff_id',
        'room',
        'is_break',
        'meta'
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'meta' => 'array',
        'period' => 'integer',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(\App\Models\Term::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'staff_id');
    }
}

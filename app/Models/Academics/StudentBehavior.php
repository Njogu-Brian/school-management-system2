<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class StudentBehavior extends Model
{
    protected $fillable = [
        'student_id',
        'behaviour_id',
        'academic_year_id',
        'term_id',
        'notes',
        'recorded_by',
    ];

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function behaviour()
    {
        return $this->belongsTo(Behavior::class, 'behaviour_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(\App\Models\Term::class);
    }

    public function teacher()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'recorded_by');
    }
}
    
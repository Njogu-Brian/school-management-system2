<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExtraCurricularActivity extends Model
{
    protected $fillable = [
        'name',
        'type',
        'day',
        'start_time',
        'end_time',
        'period',
        'academic_year_id',
        'term_id',
        'classroom_ids',
        'staff_ids',
        'description',
        'is_active',
        'repeat_weekly'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'classroom_ids' => 'array',
        'staff_ids' => 'array',
        'is_active' => 'boolean',
        'repeat_weekly' => 'boolean',
        'period' => 'integer',
    ];

    public function academicYear()
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(\App\Models\Term::class);
    }

    public function classrooms()
    {
        if (!$this->classroom_ids) {
            return collect();
        }
        return Classroom::whereIn('id', $this->classroom_ids)->get();
    }

    public function staff()
    {
        if (!$this->staff_ids) {
            return collect();
        }
        return \App\Models\Staff::whereIn('id', $this->staff_ids)->get();
    }
}

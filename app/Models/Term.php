<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolDay;

class Term extends Model
{
    protected $fillable = [
        'name', 
        'academic_year_id', 
        'is_current',
        'opening_date',
        'closing_date',
        'expected_school_days',
        'notes',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Calculate actual school days for this term
     */
    public function calculateActualSchoolDays(): int
    {
        if (!$this->opening_date || !$this->closing_date) {
            return 0;
        }
        return SchoolDay::countSchoolDays(
            $this->opening_date->toDateString(),
            $this->closing_date->toDateString()
        );
    }
}

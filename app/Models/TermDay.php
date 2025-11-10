<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Academics\AcademicYear;
use App\Models\Academics\Term;

class TermDay extends Model
{
    protected $fillable = [
        'academic_year_id',
        'term_id',
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

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Calculate actual school days for this term
     */
    public function calculateActualSchoolDays(): int
    {
        return \App\Models\SchoolDay::countSchoolDays(
            $this->opening_date,
            $this->closing_date
        );
    }
}

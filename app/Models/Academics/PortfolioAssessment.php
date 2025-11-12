<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class PortfolioAssessment extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'term_id',
        'portfolio_type',
        'title',
        'description',
        'evidence_files',
        'rubric_scores',
        'total_score',
        'performance_level_id',
        'assessed_by',
        'assessment_date',
        'status',
        'feedback'
    ];

    protected $casts = [
        'evidence_files' => 'array',
        'rubric_scores' => 'array',
        'total_score' => 'decimal:2',
        'assessment_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

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

    public function performanceLevel()
    {
        return $this->belongsTo(CBCPerformanceLevel::class, 'performance_level_id');
    }

    public function assessor()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'assessed_by');
    }

    public function examMarks()
    {
        return $this->hasMany(\App\Models\Academics\ExamMark::class, 'portfolio_assessment_id');
    }
}

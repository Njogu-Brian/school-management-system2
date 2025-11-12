<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SchemeOfWork extends Model
{
    protected $table = 'schemes_of_work';

    protected $fillable = [
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'term_id',
        'created_by',
        'title',
        'description',
        'total_lessons',
        'lessons_completed',
        'status',
        'strands_coverage',
        'substrands_coverage',
        'general_remarks',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'strands_coverage' => 'array',
        'substrands_coverage' => 'array',
        'approved_at' => 'datetime',
        'total_lessons' => 'integer',
        'lessons_completed' => 'integer',
    ];

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

    public function creator()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'approved_by');
    }

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'scheme_of_work_id');
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForClassroom(Builder $query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeForSubject(Builder $query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->whereNotNull('approved_at');
    }

    // Helper methods
    public function getProgressPercentageAttribute()
    {
        if ($this->total_lessons == 0) return 0;
        return round(($this->lessons_completed / $this->total_lessons) * 100, 2);
    }

    public function isApproved()
    {
        return !is_null($this->approved_at);
    }
}

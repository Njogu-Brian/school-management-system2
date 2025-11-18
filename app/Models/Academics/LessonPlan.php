<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LessonPlan extends Model
{
    protected $fillable = [
        'scheme_of_work_id',
        'subject_id',
        'classroom_id',
        'substrand_id',
        'academic_year_id',
        'term_id',
        'created_by',
        'title',
        'lesson_number',
        'planned_date',
        'actual_date',
        'duration_minutes',
        'learning_objectives',
        'learning_outcomes',
        'core_competencies',
        'values',
        'pclc',
        'learning_resources',
        'introduction',
        'lesson_development',
        'activities',
        'assessment',
        'conclusion',
        'reflection',
        'status',
        'execution_status',
        'challenges',
        'improvements',
        'attendance_data',
        'assessment_results'
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'duration_minutes' => 'integer',
        'learning_objectives' => 'array',
        'core_competencies' => 'array',
        'values' => 'array',
        'pclc' => 'array',
        'learning_resources' => 'array',
        'activities' => 'array',
        'attendance_data' => 'array',
        'assessment_results' => 'array',
    ];

    public function schemeOfWork()
    {
        return $this->belongsTo(SchemeOfWork::class, 'scheme_of_work_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function substrand()
    {
        return $this->belongsTo(CBCSubstrand::class, 'substrand_id');
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

    public function homework()
    {
        return $this->hasMany(Homework::class, 'lesson_plan_id');
    }

    public function homeworkDiary()
    {
        return $this->hasMany(HomeworkDiary::class, 'lesson_plan_id');
    }

    // Scopes
    public function scopePlanned(Builder $query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForClassroom(Builder $query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeForSubject(Builder $query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeUpcoming(Builder $query)
    {
        return $query->where('planned_date', '>=', now()->toDateString())
            ->where('status', 'planned');
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isOverdue()
    {
        return $this->status === 'planned' && 
               $this->planned_date < now()->toDateString();
    }
}

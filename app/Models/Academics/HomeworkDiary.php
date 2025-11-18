<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Student;
use App\Models\Academics\Homework;
use App\Models\Academics\LessonPlan;

class HomeworkDiary extends Model
{
    protected $table = 'homework_diary';

    protected $fillable = [
        'homework_id',
        'student_id',
        'lesson_plan_id',
        'status',
        'completed_at',
        'submitted_at',
        'student_notes',
        'teacher_feedback',
        'score',
        'max_score',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'integer',
        'max_score' => 'integer',
    ];

    /**
     * Get the homework this diary entry belongs to
     */
    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    /**
     * Get the student this diary entry belongs to
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the lesson plan this homework is linked to
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class, 'lesson_plan_id');
    }

    // Scopes
    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted(Builder $query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeMarked(Builder $query)
    {
        return $query->where('status', 'marked');
    }

    public function scopeForStudent(Builder $query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForHomework(Builder $query, $homeworkId)
    {
        return $query->where('homework_id', $homeworkId);
    }

    // Helper methods
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted' || $this->status === 'marked';
    }

    public function isMarked(): bool
    {
        return $this->status === 'marked';
    }

    public function getPercentageAttribute(): float
    {
        if (!$this->max_score || $this->max_score == 0 || $this->score === null) {
            return 0;
        }
        return round(($this->score / $this->max_score) * 100, 2);
    }

    public function isLate(): bool
    {
        if (!$this->homework || !$this->submitted_at) {
            return false;
        }
        return $this->submitted_at->gt($this->homework->due_date);
    }
}


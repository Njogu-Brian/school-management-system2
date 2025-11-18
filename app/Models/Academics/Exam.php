<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Term;
use App\Models\AcademicYear;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use Carbon\Carbon;

class Exam extends Model
{
    protected $fillable = [
        'name',
        'type',
        'modality',
        'academic_year_id',
        'term_id',
        'classroom_id',
        'stream_id',
        'subject_id',
        'created_by',
        'starts_on',
        'ends_on',
        'max_marks',
        'weight',
        'status',
        'publish_exam',
        'publish_result',
        'published_at',
        'locked_at',
        'settings',
        // CBC fields
        'is_cat',
        'cat_number',
        'assessment_method',
        'competency_focus',
        'portfolio_required',
        'sba_weight',
        // Advanced exam features
        'exam_type_id',
        'exam_category',
        'component_weights',
        'grade_mapping',
        'descriptor_mapping',
        'import_template_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'locked_at'    => 'datetime',
        'settings'     => 'array',
        'starts_on'    => 'date',
        'ends_on'      => 'date',
        'max_marks'    => 'decimal:2',
        'weight'       => 'decimal:2',
        'publish_exam' => 'boolean',
        'publish_result' => 'boolean',
        // CBC fields
        'is_cat' => 'boolean',
        'cat_number' => 'integer',
        'competency_focus' => 'array',
        'portfolio_required' => 'boolean',
        'sba_weight' => 'decimal:2',
        // Advanced exam features
        'component_weights' => 'array',
        'grade_mapping' => 'array',
        'descriptor_mapping' => 'array',
    ];

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function stream()
    {
        return $this->belongsTo(\App\Models\Stream::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // Many-to-Many with classrooms
    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'exam_class_subject')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    // Many-to-Many with subjects
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'exam_class_subject')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }

    public function papers()
    {
        return $this->hasMany(ExamPaper::class);
    }

    public function marks()
    {
        return $this->hasMany(ExamMark::class);
    }

    public function schedules()
    {
        return $this->hasMany(ExamSchedule::class);
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('status', '!=', 'locked');
    }

    public function scopePublished(Builder $query)
    {
        return $query->where('publish_exam', true);
    }

    public function scopeForYear(Builder $query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    public function scopeForTerm(Builder $query, $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'secondary',
            'open' => 'info',
            'marking' => 'warning',
            'moderation' => 'warning',
            'approved' => 'success',
            'published' => 'success',
            'locked' => 'danger',
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getIsOpenAttribute()
    {
        return $this->status === 'open';
    }

    public function getCanEnterMarksAttribute()
    {
        return in_array($this->status, ['open', 'marking']);
    }

    public function getCanPublishAttribute()
    {
        return in_array($this->status, ['approved', 'published']);
    }

    public function getIsLockedAttribute()
    {
        return $this->status === 'locked';
    }

    public function getMarksCountAttribute()
    {
        return $this->marks()->count();
    }

    public function getStudentsCountAttribute()
    {
        if ($this->classroom_id) {
            $query = \App\Models\Student::where('classroom_id', $this->classroom_id);
            if ($this->stream_id) {
                $query->where('stream_id', $this->stream_id);
            }
            return $query->count();
        }
        return 0;
    }

    // Methods
    public function canTransitionTo($newStatus)
    {
        $transitions = [
            'draft' => ['open'],
            'open' => ['marking', 'draft'],
            'marking' => ['moderation', 'open'],
            'moderation' => ['approved', 'marking'],
            'approved' => ['published', 'locked', 'moderation'],
            'published' => ['locked', 'approved'],
            'locked' => [],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? []);
    }

    public function isWithinDateRange()
    {
        if (!$this->starts_on || !$this->ends_on) {
            return true; // No date restriction
        }

        $now = Carbon::now();
        return $now->between($this->starts_on, $this->ends_on);
    }
}

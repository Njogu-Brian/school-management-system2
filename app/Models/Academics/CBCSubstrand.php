<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CBCSubstrand extends Model
{
    protected $table = 'cbc_substrands';

    protected $fillable = [
        'strand_id',
        'code',
        'name',
        'description',
        'learning_outcomes',
        'key_inquiry_questions',
        'core_competencies',
        'values',
        'pclc',
        'suggested_lessons',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'learning_outcomes' => 'array',
        'key_inquiry_questions' => 'array',
        'core_competencies' => 'array',
        'values' => 'array',
        'pclc' => 'array',
        'suggested_lessons' => 'integer',
        'display_order' => 'integer',
    ];

    public function strand()
    {
        return $this->belongsTo(CBCStrand::class, 'strand_id');
    }

    /**
     * Get all lesson plans for this substrand
     */
    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'substrand_id');
    }

    /**
     * Get all competencies for this substrand
     */
    public function competencies()
    {
        return $this->hasMany(Competency::class, 'substrand_id')->orderBy('display_order');
    }

    /**
     * Get active competencies only
     */
    public function activeCompetencies()
    {
        return $this->hasMany(Competency::class, 'substrand_id')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Get suggested learning experiences for this substrand
     */
    public function suggestedExperiences()
    {
        return $this->hasMany(\App\Models\SuggestedExperience::class, 'substrand_id')->orderBy('order');
    }

    /**
     * Get assessment rubrics for this substrand
     */
    public function assessmentRubrics()
    {
        return $this->hasMany(\App\Models\AssessmentRubric::class, 'substrand_id')->orderBy('order');
    }

    /**
     * Get the learning area through strand
     */
    public function learningArea()
    {
        return $this->hasOneThrough(
            LearningArea::class,
            CBCStrand::class,
            'id', // Foreign key on CBCStrand
            'id', // Foreign key on LearningArea
            'strand_id', // Local key on CBCSubstrand
            'learning_area_id' // Local key on CBCStrand
        );
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}

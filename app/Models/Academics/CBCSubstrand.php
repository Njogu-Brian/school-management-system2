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

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'substrand_id');
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

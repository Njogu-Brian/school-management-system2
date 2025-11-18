<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Competency extends Model
{
    protected $table = 'competencies';

    protected $fillable = [
        'substrand_id',
        'code',
        'name',
        'description',
        'indicators',
        'assessment_criteria',
        'competency_level',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'indicators' => 'array',
        'assessment_criteria' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the substrand this competency belongs to
     */
    public function substrand()
    {
        return $this->belongsTo(CBCSubstrand::class, 'substrand_id');
    }

    /**
     * Get the strand through substrand
     */
    public function strand()
    {
        return $this->hasOneThrough(
            CBCStrand::class,
            CBCSubstrand::class,
            'id', // Foreign key on CBCSubstrand
            'id', // Foreign key on CBCStrand
            'substrand_id', // Local key on Competency
            'strand_id' // Local key on CBCSubstrand
        );
    }

    /**
     * Get the learning area through substrand and strand
     * Using a more direct approach via substrand relationship
     */
    public function getLearningAreaAttribute()
    {
        return $this->substrand?->strand?->learningArea;
    }

    /**
     * Query scope to filter by learning area
     */
    public function scopeForLearningArea(Builder $query, $learningAreaId)
    {
        return $query->whereHas('substrand.strand', function ($q) use ($learningAreaId) {
            $q->where('learning_area_id', $learningAreaId);
        });
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel(Builder $query, $level)
    {
        return $query->whereHas('substrand.strand', function ($q) use ($level) {
            $q->where('level', $level);
        });
    }

    /**
     * Query scope to filter by substrand
     */
    public function scopeForSubstrand(Builder $query, $substrandId)
    {
        return $query->where('substrand_id', $substrandId);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}

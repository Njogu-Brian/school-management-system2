<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CBCStrand extends Model
{
    protected $table = 'cbc_strands';

    protected $fillable = [
        'code',
        'name',
        'description',
        'learning_area', // Keep for backward compatibility
        'learning_area_id', // New foreign key
        'curriculum_design_id',
        'level',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the learning area this strand belongs to
     */
    public function learningArea()
    {
        return $this->belongsTo(LearningArea::class, 'learning_area_id');
    }

    /**
     * Get all substrands for this strand
     */
    public function substrands()
    {
        return $this->hasMany(CBCSubstrand::class, 'strand_id')->orderBy('display_order');
    }

    /**
     * Get active substrands only
     */
    public function activeSubstrands()
    {
        return $this->hasMany(CBCSubstrand::class, 'strand_id')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Get all competencies through substrands
     */
    public function competencies()
    {
        return $this->hasManyThrough(
            Competency::class,
            CBCSubstrand::class,
            'strand_id', // Foreign key on CBCSubstrand
            'substrand_id', // Foreign key on Competency
            'id', // Local key on CBCStrand
            'id' // Local key on CBCSubstrand
        );
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel(Builder $query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForLearningArea(Builder $query, $learningArea)
    {
        // Support both string and ID lookups
        if (is_numeric($learningArea)) {
            return $query->where('learning_area_id', $learningArea);
        }
        return $query->where('learning_area', $learningArea)
            ->orWhereHas('learningArea', function ($q) use ($learningArea) {
                $q->where('code', $learningArea)->orWhere('name', $learningArea);
            });
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}

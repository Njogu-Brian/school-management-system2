<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LearningArea extends Model
{
    protected $table = 'learning_areas';

    protected $fillable = [
        'code',
        'name',
        'description',
        'curriculum_design_id',
        'level_category',
        'levels',
        'display_order',
        'is_active',
        'is_core',
    ];

    protected $casts = [
        'levels' => 'array',
        'is_active' => 'boolean',
        'is_core' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all strands for this learning area
     */
    public function strands()
    {
        return $this->hasMany(CBCStrand::class, 'learning_area_id')->orderBy('display_order');
    }

    /**
     * Get active strands only
     */
    public function activeStrands()
    {
        return $this->hasMany(CBCStrand::class, 'learning_area_id')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Get strands for a specific level
     */
    public function strandsForLevel($level)
    {
        return $this->strands()->where('level', $level);
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCore(Builder $query)
    {
        return $query->where('is_core', true);
    }

    public function scopeForLevel(Builder $query, $level)
    {
        return $query->whereJsonContains('levels', $level);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}


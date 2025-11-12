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
        'learning_area',
        'level',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function substrands()
    {
        return $this->hasMany(CBCSubstrand::class, 'strand_id')->orderBy('display_order');
    }

    public function activeSubstrands()
    {
        return $this->hasMany(CBCSubstrand::class, 'strand_id')
            ->where('is_active', true)
            ->orderBy('display_order');
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
        return $query->where('learning_area', $learningArea);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}

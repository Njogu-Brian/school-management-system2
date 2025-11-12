<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SubjectGroup extends Model
{
    protected $fillable = [
        'name',
        'code',
        'display_order',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function activeSubjects()
    {
        return $this->hasMany(Subject::class)->where('is_active', true);
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

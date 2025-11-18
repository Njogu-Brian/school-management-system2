<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRubric extends Model
{
    protected $fillable = [
        'substrand_id',
        'rubric_json',
        'order',
    ];

    protected $casts = [
        'rubric_json' => 'array',
        'order' => 'integer',
    ];

    /**
     * Get the substrand this rubric belongs to
     */
    public function substrand(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Academics\CBCSubstrand::class, 'substrand_id');
    }

    /**
     * Get the rubric as a structured array
     */
    public function getRubricAttribute(): array
    {
        return $this->rubric_json ?? [];
    }
}

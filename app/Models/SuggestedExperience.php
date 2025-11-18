<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuggestedExperience extends Model
{
    protected $fillable = [
        'substrand_id',
        'content',
        'examples',
        'order',
        'metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the substrand this suggested experience belongs to
     */
    public function substrand(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Academics\CBCSubstrand::class, 'substrand_id');
    }
}

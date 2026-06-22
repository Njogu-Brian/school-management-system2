<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaQualityFlag extends Model
{
    protected $fillable = [
        'media_id',
        'approved',
        'hero_ready',
        'homepage_ready',
        'priority',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'hero_ready' => 'boolean',
        'homepage_ready' => 'boolean',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaLibraryItem::class, 'media_id');
    }
}

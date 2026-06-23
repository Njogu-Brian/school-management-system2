<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'relationship',
        'message',
        'photo',
        'media_id',
        'video_url',
        'featured',
        'approved',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'approved' => 'boolean',
    ];

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaLibraryItem::class, 'media_id');
    }

    public function photoUrl(): ?string
    {
        if ($this->relationLoaded('mediaItem') && $this->mediaItem) {
            return $this->mediaItem->urlForSize('md');
        }

        return $this->photo ? asset('website/'.$this->photo) : null;
    }

    public function photoSrcset(): ?string
    {
        if ($this->relationLoaded('mediaItem') && $this->mediaItem) {
            return $this->mediaItem->srcset();
        }

        return null;
    }
}

<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebsiteEvent extends Model
{
    protected $table = 'website_events';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'cover_image',
        'location',
        'registration_enabled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (WebsiteEvent $event) {
            if (empty($event->slug) && ! empty($event->title)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image ? asset('website/'.$this->cover_image) : null;
    }
}

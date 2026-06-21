<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'school_name',
        'tagline',
        'primary_color',
        'secondary_color',
        'phone',
        'email',
        'address',
        'google_map',
        'whatsapp',
        'facebook',
        'instagram',
        'youtube',
        'tiktok',
        'hero_video',
        'logo',
        'favicon',
        'admissions_open',
        'current_term',
        'seo_defaults',
    ];

    protected $casts = [
        'admissions_open' => 'boolean',
        'seo_defaults' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'school_name' => setting('school_name', 'Royal Kings Education Centre'),
            'tagline' => 'Where Little Steps Grow Into Great Futures',
            'primary_color' => '#5B2C8E',
            'secondary_color' => '#D4AF37',
        ]);
    }
}

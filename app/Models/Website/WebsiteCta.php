<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class WebsiteCta extends Model
{
    protected $fillable = [
        'name', 'cta_type', 'label', 'url', 'placement', 'pages', 'is_active', 'click_count',
    ];

    protected $casts = [
        'pages' => 'array',
        'is_active' => 'boolean',
    ];
}

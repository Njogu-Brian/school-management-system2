<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class SectionTemplate extends Model
{
    protected $fillable = ['name', 'type', 'default_content', 'settings', 'preview_image', 'is_active'];

    protected $casts = [
        'default_content' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];
}

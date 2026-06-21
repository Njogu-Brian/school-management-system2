<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class FamilyStory extends Model
{
    protected $fillable = ['family_name', 'story', 'cover_image', 'published', 'featured'];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
    ];
}

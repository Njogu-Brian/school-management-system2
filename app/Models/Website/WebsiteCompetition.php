<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class WebsiteCompetition extends Model
{
    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'category',
        'result',
        'published',
    ];

    protected $casts = [
        'date' => 'date',
        'published' => 'boolean',
    ];
}

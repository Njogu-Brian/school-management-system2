<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class PrayerRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'request',
        'is_anonymous',
        'status',
        'is_public',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_public' => 'boolean',
    ];
}

<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class ConversionEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_type', 'page', 'metadata', 'visitor_id', 'occurred_at'];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
}

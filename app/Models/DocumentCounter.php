<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCounter extends Model
{
    protected $fillable = [
        'type',
        'prefix',
        'suffix',
        'padding_length',
        'next_number',
        'reset_period',
        'last_reset_year',
        'last_reset_month',
    ];

    protected $casts = [
        'padding_length' => 'integer',
        'next_number' => 'integer',
        'last_reset_year' => 'integer',
        'last_reset_month' => 'integer',
    ];
}


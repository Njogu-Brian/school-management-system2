<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatutoryRuleset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'effective_from',
        'effective_to',
        'is_default',
        'params',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_default' => 'boolean',
        'params' => 'array',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}


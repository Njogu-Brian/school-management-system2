<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableLayoutTemplate extends Model
{
    protected $fillable = [
        'name',
        'days_active',
        'default_start_time',
        'default_end_time',
        'meta',
    ];

    protected $casts = [
        'days_active' => 'array',
        'meta' => 'array',
    ];

    public function periods()
    {
        return $this->hasMany(TimetableLayoutPeriod::class, 'template_id')->orderBy('day')->orderBy('sort_order');
    }
}


<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableLayoutPeriod extends Model
{
    protected $fillable = [
        'template_id',
        'day',
        'sort_order',
        'start_time',
        'end_time',
        'slot_type',
        'label',
        'can_combine',
        'combine_size',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'can_combine' => 'boolean',
        'combine_size' => 'integer',
        'meta' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(TimetableLayoutTemplate::class, 'template_id');
    }
}


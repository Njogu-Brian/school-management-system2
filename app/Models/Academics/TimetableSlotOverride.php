<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableSlotOverride extends Model
{
    protected $fillable = [
        'run_id',
        'stream_id',
        'layout_period_id',
        'day',
        'effective_date',
        'slot_type',
        'subject_id',
        'staff_id',
        'label',
        'room',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];
}


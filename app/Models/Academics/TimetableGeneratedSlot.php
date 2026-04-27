<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableGeneratedSlot extends Model
{
    protected $fillable = [
        'run_id',
        'stream_id',
        'layout_period_id',
        'day',
        'slot_type',
        'subject_id',
        'staff_id',
        'label',
        'room',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(TimetableGenerationRun::class, 'run_id');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function layoutPeriod()
    {
        return $this->belongsTo(TimetableLayoutPeriod::class, 'layout_period_id');
    }
}


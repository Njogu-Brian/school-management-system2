<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableSlotLock extends Model
{
    protected $fillable = [
        'run_id',
        'stream_id',
        'layout_period_id',
        'day',
        'locked_subject_id',
        'locked_staff_id',
        'locked_label',
        'locked_room',
        'reason',
        'locked_by',
    ];

    public function run()
    {
        return $this->belongsTo(TimetableGenerationRun::class, 'run_id');
    }
}


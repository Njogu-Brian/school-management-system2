<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledCommunication extends Model
{
    protected $fillable = [
        'type',
        'template_id',
        'message',
        'target',
        'sender_id',
        'classroom_id',
        'classroom_ids',
        'fee_balance_only',
        'no_fee_balance_only',
        'exclude_staff',
        'exclude_student_ids',
        'send_at',
        'status',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'fee_balance_only' => 'boolean',
        'no_fee_balance_only' => 'boolean',
        'exclude_staff' => 'boolean',
    ];

    // Optional: keep old blade name working
    public function getScheduledAtAttribute()
    {
        return $this->send_at;
    }

    public function template()
    {
        return $this->belongsTo(\App\Models\CommunicationTemplate::class);
    }
}

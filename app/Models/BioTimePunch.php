<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioTimePunch extends Model
{
    protected $table = 'biotime_punches';

    protected $fillable = [
        'biotime_transaction_id',
        'emp_code',
        'staff_id',
        'punch_time',
        'punch_state',
        'terminal_sn',
        'terminal_alias',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

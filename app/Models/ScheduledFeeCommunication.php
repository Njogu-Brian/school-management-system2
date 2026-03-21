<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledFeeCommunication extends Model
{
    protected $fillable = [
        'target',
        'student_id',
        'selected_student_ids',
        'classroom_ids',
        'filter_type',
        'balance_min',
        'balance_max',
        'balance_percent_min',
        'balance_percent_max',
        'channels',
        'template_id',
        'custom_message',
        'send_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'selected_student_ids' => 'array',
        'classroom_ids' => 'array',
        'channels' => 'array',
        'send_at' => 'datetime',
        'balance_min' => 'decimal:2',
        'balance_max' => 'decimal:2',
        'balance_percent_min' => 'decimal:2',
        'balance_percent_max' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function template()
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('send_at', '<=', now());
    }
}

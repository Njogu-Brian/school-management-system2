<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationJobRecipient extends Model
{
    protected $fillable = [
        'communication_job_id',
        'contact',
        'name',
        'recipient_type',
        'recipient_id',
        'status',
        'error_code',
        'error_message',
        'sent_at',
        'communication_log_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(CommunicationJob::class, 'communication_job_id');
    }

    public function communicationLog(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class, 'communication_log_id');
    }
}

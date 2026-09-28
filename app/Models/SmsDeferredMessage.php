<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsDeferredMessage extends Model
{
    protected $fillable = [
        'kind',
        'contact',
        'message',
        'sender_id',
        'title',
        'scope',
        'recipient_type',
        'recipient_id',
        'meta',
        'status',
        'expires_at',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

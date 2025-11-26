<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    protected $fillable = [
        'gateway',
        'event_type',
        'event_id',
        'payload',
        'signature',
        'processed',
        'processing_error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Scope to get unprocessed webhooks
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope to filter by gateway
     */
    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed(?string $error = null): void
    {
        $this->update([
            'processed' => true,
            'processing_error' => $error,
            'processed_at' => now(),
        ]);
    }
}


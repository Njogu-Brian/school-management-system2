<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class CommunicationJob extends Model
{
    protected $fillable = [
        'uuid',
        'tracking_id',
        'source',
        'channel',
        'title',
        'message',
        'status',
        'pause_reason',
        'scheduled_at',
        'started_at',
        'finished_at',
        'created_by',
        'recipient_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'source_ref_type',
        'source_ref_id',
        'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $job) {
            if (!$job->uuid) {
                $job->uuid = (string) Str::uuid();
            }
        });
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CommunicationJobRecipient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceRef(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_ref_type', 'source_ref_id');
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'scheduled', 'running', 'paused'], true);
    }

    public function isPausable(): bool
    {
        return in_array($this->status, ['pending', 'scheduled', 'running'], true);
    }

    public function isResumable(): bool
    {
        return $this->status === 'paused';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending', 'scheduled' => 'bg-warning text-dark',
            'running' => 'bg-primary',
            'paused' => 'bg-info text-dark',
            'completed' => 'bg-success',
            'cancelled' => 'bg-secondary',
            'failed' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}

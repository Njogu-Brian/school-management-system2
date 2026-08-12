<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentForcedAction extends Model
{
    public const TYPE_CHANGE_PASSWORD = 'change_password';

    public const TYPE_PROFILE_REVIEW = 'profile_review';

    public const TYPE_UPLOAD_DOCUMENTS = 'upload_documents';

    public const TYPE_CUSTOM_FORM = 'custom_form';

    public const TYPE_ACKNOWLEDGE = 'acknowledge';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'user_id',
        'parent_info_id',
        'type',
        'title',
        'payload',
        'priority',
        'blocking',
        'status',
        'due_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'blocking' => 'boolean',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentInfo(): BelongsTo
    {
        return $this->belongsTo(ParentInfo::class, 'parent_info_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function markCompleted(?array $payloadMerge = null): void
    {
        $payload = $this->payload ?? [];
        if ($payloadMerge) {
            $payload = array_merge($payload, $payloadMerge);
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'payload' => $payload,
        ]);
    }
}

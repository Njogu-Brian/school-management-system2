<?php

namespace App\Models\Website;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiContentLog extends Model
{
    public const TYPES = [
        'blog',
        'announcement',
        'newsletter',
        'event_recap',
        'admissions_copy',
        'social_media_caption',
        'parent_message',
        'fee_reminder',
    ];

    protected $fillable = [
        'user_id',
        'content_type',
        'prompt',
        'output',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppClientIssue extends Model
{
    protected $fillable = [
        'user_id',
        'app',
        'platform',
        'app_version',
        'role',
        'message',
        'stack',
        'component_stack',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

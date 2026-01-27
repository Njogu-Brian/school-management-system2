<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeesComparisonPreview extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'term',
        'preview_data',
        'has_issues',
    ];

    protected $casts = [
        'preview_data' => 'array',
        'has_issues' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPreviewRows(): array
    {
        return $this->preview_data['preview'] ?? [];
    }

    public function getSummary(): array
    {
        return $this->preview_data['summary'] ?? [];
    }
}

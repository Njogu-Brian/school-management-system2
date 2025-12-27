<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyVoteheadMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_label',
        'votehead_id',
        'status',
        'created_by',
        'resolved_by',
    ];

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}


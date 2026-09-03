<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentActivityChangeRequest extends Model
{
    protected $fillable = [
        'student_id',
        'votehead_id',
        'year',
        'term',
        'action',
        'status',
        'requested_amount',
        'parent_note',
        'review_note',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'term' => 'integer',
        'requested_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

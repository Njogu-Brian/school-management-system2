<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostel_id',
        'room_number',
        'room_type',
        'capacity',
        'current_occupancy',
        'floor',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'current_occupancy' => 'integer',
        'floor' => 'integer',
    ];

    /**
     * Get the hostel
     */
    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    /**
     * Get allocations for this room
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class)->where('status', 'active');
    }

    /**
     * Check if room has available space
     */
    public function hasAvailableSpace(): bool
    {
        return $this->current_occupancy < $this->capacity && $this->status === 'available';
    }
}


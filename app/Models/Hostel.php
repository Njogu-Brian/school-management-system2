<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'capacity',
        'current_occupancy',
        'warden_id',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'current_occupancy' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the warden (staff member)
     */
    public function warden(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'warden_id');
    }

    /**
     * Get all rooms in this hostel
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }

    /**
     * Get active allocations
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class)->where('status', 'active');
    }

    /**
     * Get hostel fees
     */
    public function fees(): HasMany
    {
        return $this->hasMany(HostelFee::class);
    }

    /**
     * Check if hostel has available space
     */
    public function hasAvailableSpace(): bool
    {
        return $this->current_occupancy < $this->capacity;
    }
}


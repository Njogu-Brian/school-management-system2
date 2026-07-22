<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripRun extends Model
{
    protected $fillable = [
        'trip_id',
        'run_date',
        'driver_id',
        'vehicle_id',
        'status',
        'started_at',
        'ended_at',
        'last_latitude',
        'last_longitude',
        'last_accuracy_meters',
        'last_speed_kmh',
        'last_location_at',
        'started_by',
    ];

    protected $casts = [
        'run_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_location_at' => 'datetime',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_accuracy_meters' => 'float',
        'last_speed_kmh' => 'float',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TripRunLocation::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'in_progress'
            && $this->last_latitude !== null
            && $this->last_longitude !== null;
    }
}

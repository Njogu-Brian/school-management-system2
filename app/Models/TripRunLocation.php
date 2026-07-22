<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRunLocation extends Model
{
    protected $fillable = [
        'trip_run_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'speed_kmh',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_meters' => 'float',
        'speed_kmh' => 'float',
        'heading' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function tripRun(): BelongsTo
    {
        return $this->belongsTo(TripRun::class);
    }
}

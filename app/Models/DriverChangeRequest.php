<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverChangeRequest extends Model
{
    protected $fillable = [
        'driver_id',
        'trip_id',
        'request_type',
        'requested_trip_id',
        'requested_drop_off_point_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Staff::class, 'driver_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function requestedTrip()
    {
        return $this->belongsTo(Trip::class, 'requested_trip_id');
    }

    public function requestedDropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class, 'requested_drop_off_point_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

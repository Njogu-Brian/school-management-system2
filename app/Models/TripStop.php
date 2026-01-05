<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripStop extends Model
{
    protected $fillable = [
        'trip_id',
        'drop_off_point_id',
        'sequence_order',
        'estimated_time',
    ];

    protected $casts = [
        'estimated_time' => 'datetime:H:i',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function dropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class);
    }
}

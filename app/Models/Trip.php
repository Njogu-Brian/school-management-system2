<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_name', // actual column
        'vehicle_id',
        'driver_id',
        'day_of_week',
        'direction',
    ];

    protected $casts = [
        'day_of_week' => 'array',
    ];

    /**
     * Backwards-compatibility: allow $trip->name and mass-assigning `name`
     * even though the database column is `trip_name`.
     */
    public function getNameAttribute(): ?string
    {
        return $this->trip_name;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['trip_name'] = $value;
    }

    // Relationship with Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Relationship with Students through StudentAssignment
    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class);
    }

    // Relationship with Driver (Staff)
    public function driver()
    {
        return $this->belongsTo(Staff::class, 'driver_id');
    }

    // Relationship with Trip Stops
    public function stops()
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence_order');
    }

    // Relationship with Trip Attendances
    public function attendances()
    {
        return $this->hasMany(TripAttendance::class);
    }
}

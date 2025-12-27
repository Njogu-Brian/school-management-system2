<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_name', // actual column
        'type',
        'route_id',
        'vehicle_id',
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

    // Relationship with Route
    public function route()
    {
        return $this->belongsTo(Route::class);
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'route_id',
        'vehicle_id',
    ];

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

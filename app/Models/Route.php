<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'area',
    ];

    // Relationship with Vehicles (Many-to-Many)
    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class);
    }

    // Relationship with Trips
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    // Relationship with DropOffPoints
    public function dropOffPoints()
    {
        return $this->hasMany(DropOffPoint::class);
    }

    // Relationship with Student Assignments
    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class);
    }
}

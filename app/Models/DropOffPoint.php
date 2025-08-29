<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Route;
use App\Models\StudentAssignment;
use App\Models\Vehicle;

class DropOffPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'route_id',
    ];

    // Relationship with Route
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    // Relationship with Student Assignments
    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class, 'drop_off_point_id');
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'drop_off_point_vehicle')
            ->withTimestamps();
    }
    // app/Models/Vehicle.php
    public function dropOffPoints()
    {
        return $this->belongsToMany(DropOffPoint::class, 'drop_off_point_vehicle')
            ->withTimestamps();
    }

}

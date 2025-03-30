<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'driver_name',
        'make',
        'model',
        'type',
        'capacity',
        'chassis_number',
        'insurance_document',
        'logbook_document',
    ];

    // Relationship with Routes (Many-to-Many)
    public function routes()
    {
        return $this->belongsToMany(Route::class);
    }

    // Relationship with Trips
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}

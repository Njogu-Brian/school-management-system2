<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'make',
        'model',
        'type',
        'capacity',
        'chassis_number',
        'driver_name',
        'insurance_document',
        'logbook_document',
    ];

    /**
     * Get all trips assigned to this vehicle.
     */
    public function trips()
    {
        return $this->hasMany(\App\Models\Trip::class, 'vehicle_id');
    }
}

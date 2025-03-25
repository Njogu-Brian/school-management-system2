<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Trip;

class Transport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transport';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'driver_name',
        'vehicle_number',
        'phone_number', // Add this if you've added the phone_number column via migration
    ];

    /**
     * Get the students assigned to this transport route.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'route_id');
    }

    /**
     * Get the trips associated with this transport.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'transport_id');
    }
}
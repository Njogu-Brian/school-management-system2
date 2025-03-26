<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    // ✅ Remove this since Laravel automatically assumes 'trips'
    // protected $table = 'trip';

    protected $primaryKey = 'id'; // optional — this is also assumed by default

    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'route_id',
        'vehicle_id',
        'drop_off_point',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}

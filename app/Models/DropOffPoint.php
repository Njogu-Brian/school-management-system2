<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}

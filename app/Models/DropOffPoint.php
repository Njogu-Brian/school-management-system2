<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Route;
use App\Models\StudentAssignment;

class DropOffPoint extends Model
{
    protected $fillable = ['route_id', 'point_name'];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function studentAssignments()
    {
        return $this->hasMany(StudentAssignment::class);
    }
}

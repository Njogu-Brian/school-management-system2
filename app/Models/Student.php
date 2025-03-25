<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transport;
use App\Models\Attendance;

class Student extends Model
{
    protected $fillable = ['admission_number', 'name', 'class', 'parent_id', 'archive'];

    // Relationship with Parent
    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    // ✅ Updated to `attendances()` for consistency
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Relationship with Transport
    public function route()
    {
        return $this->belongsTo(Transport::class, 'route_id');
    }

    // Fetch today's attendance for a student
    public function attendanceForToday()
    {
        return $this->hasOne(Attendance::class)
            ->whereDate('date', today());
    }
}

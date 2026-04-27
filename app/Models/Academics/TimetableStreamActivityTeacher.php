<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableStreamActivityTeacher extends Model
{
    protected $fillable = [
        'activity_requirement_id',
        'staff_id',
        'periods_per_week',
        'meta',
    ];

    protected $casts = [
        'periods_per_week' => 'integer',
        'meta' => 'array',
    ];

    public function activityRequirement()
    {
        return $this->belongsTo(TimetableStreamActivityRequirement::class, 'activity_requirement_id');
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'staff_id');
    }
}


<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableStreamActivityRequirement extends Model
{
    protected $fillable = [
        'stream_id',
        'academic_year_id',
        'term_id',
        'name',
        'periods_per_week',
        'is_teacher_assigned',
        'meta',
    ];

    protected $casts = [
        'periods_per_week' => 'integer',
        'is_teacher_assigned' => 'boolean',
        'meta' => 'array',
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function teachers()
    {
        return $this->hasMany(TimetableStreamActivityTeacher::class, 'activity_requirement_id');
    }
}


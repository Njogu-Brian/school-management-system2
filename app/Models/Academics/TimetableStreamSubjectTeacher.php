<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableStreamSubjectTeacher extends Model
{
    protected $fillable = [
        'stream_id',
        'academic_year_id',
        'term_id',
        'subject_id',
        'staff_id',
        'periods_per_week',
        'meta',
    ];

    protected $casts = [
        'periods_per_week' => 'integer',
        'meta' => 'array',
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'staff_id');
    }
}


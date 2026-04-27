<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableStreamSubjectRequirement extends Model
{
    protected $fillable = [
        'stream_id',
        'academic_year_id',
        'term_id',
        'subject_id',
        'periods_per_week',
        'allow_double',
        'max_doubles_per_week',
        'meta',
    ];

    protected $casts = [
        'periods_per_week' => 'integer',
        'allow_double' => 'boolean',
        'max_doubles_per_week' => 'integer',
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
}


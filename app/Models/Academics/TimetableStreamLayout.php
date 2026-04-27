<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableStreamLayout extends Model
{
    protected $fillable = [
        'stream_id',
        'academic_year_id',
        'term_id',
        'template_id',
        'overrides',
    ];

    protected $casts = [
        'overrides' => 'array',
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function template()
    {
        return $this->belongsTo(TimetableLayoutTemplate::class, 'template_id');
    }
}


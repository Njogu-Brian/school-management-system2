<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Diary extends Model
{
    protected $fillable = ['classroom_id','stream_id','teacher_id','week_start','entries'];

    protected $casts = [
        'week_start' => 'date',
        'entries' => 'array',
    ];

    public function teacher()
    {
        return $this->belongsTo(\App\Models\Staff::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }
}


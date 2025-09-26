<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'student_id','academic_year_id','term_id','classroom_id',
        'stream_id','pdf_path','published_at','published_by','locked_at','summary'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'summary' => 'array',
    ];

    public function student() { return $this->belongsTo(\App\Models\Student::class); }
    public function publisher() { return $this->belongsTo(\App\Models\Staff::class,'published_by'); }
}

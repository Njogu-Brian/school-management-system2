<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ClassroomSubject extends Model
{
    protected $fillable = [
        'classroom_id','stream_id','subject_id','staff_id',
        'academic_year_id','term_id','is_compulsory'
    ];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(\App\Models\Staff::class,'staff_id'); }
}

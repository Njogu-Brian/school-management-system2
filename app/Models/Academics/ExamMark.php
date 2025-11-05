<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    protected $fillable = [
        'exam_id','student_id','subject_id','teacher_id',
        'score_raw','score_moderated',
        'opener_score','midterm_score','endterm_score',
        'rubrics','grade_label','pl_level',
        'subject_remark','remark','status','audit'
    ];

    protected $casts = [
        'rubrics' => 'array',
        'audit'   => 'array',
    ];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function student() { return $this->belongsTo(\App\Models\Student::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(\App\Models\Staff::class,'teacher_id'); }
    
    protected static function booted() {
        static::creating(function($m){
            $m->entered_by = auth()->id();
            $m->updated_by = auth()->id();
        });
        static::updating(function($m){
            $m->updated_by = auth()->id();
        });
    }

}

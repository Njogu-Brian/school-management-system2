<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    protected $fillable = [
        'exam_id','student_id','subject_id','teacher_id',
        'score_raw','score_moderated','grade_label','pl_level',
        'remark','status','audit'
    ];

    protected $casts = ['audit' => 'array'];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function student() { return $this->belongsTo(\App\Models\Student::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(\App\Models\Staff::class,'teacher_id'); }
}

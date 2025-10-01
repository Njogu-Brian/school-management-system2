<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamPaper extends Model
{
    protected $fillable = [
        'exam_id',
        'subject_id',
        'classroom_id',
        'exam_date',
        'start_time',
        'end_time',
        'max_marks',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}

<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    protected $table = 'exam_schedules';

    protected $fillable = [
        'exam_id','subject_id','classroom_id',
        'exam_date','start_time','end_time',
        'duration_minutes','min_mark','max_mark','weight',
        'room','invigilator_id'
    ];

    public function exam(){ return $this->belongsTo(Exam::class); }
    public function subject(){ return $this->belongsTo(Subject::class); }
    public function classroom(){ return $this->belongsTo(Classroom::class); }
}

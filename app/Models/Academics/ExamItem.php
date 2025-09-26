<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamItem extends Model
{
    protected $fillable = ['exam_id','qtype','question','options','correct_answers','marks','rubric','position'];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
        'rubric' => 'array',
    ];

    public function exam() { return $this->belongsTo(Exam::class); }
}

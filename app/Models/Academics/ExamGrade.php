<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamGrade extends Model
{
    protected $fillable = [
        'exam_type','grade_name','percent_from','percent_upto','grade_point','description'
    ];
}

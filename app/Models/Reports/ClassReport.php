<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Academics\Classroom;
use App\Models\Staff;

class ClassReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_ending',
        'campus',
        'classroom_id',
        'class_teacher_id',
        'total_learners',
        'frequent_absentees',
        'discipline_level',
        'homework_completion',
        'learners_struggling',
        'learners_improved',
        'parents_to_contact',
        'classroom_condition',
        'notes',
        'academic_group',
    ];

    protected $casts = [
        'week_ending' => 'date',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function classTeacher()
    {
        return $this->belongsTo(Staff::class, 'class_teacher_id');
    }
}

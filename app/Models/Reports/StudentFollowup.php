<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Student;
use App\Models\Academics\Classroom;

class StudentFollowup extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_ending',
        'campus',
        'student_id',
        'classroom_id',
        'academic_concern',
        'behavior_concern',
        'action_taken',
        'parent_contacted',
        'progress_status',
        'notes',
    ];

    protected $casts = [
        'week_ending' => 'date',
        'academic_concern' => 'boolean',
        'behavior_concern' => 'boolean',
        'parent_contacted' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}

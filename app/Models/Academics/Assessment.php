<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Student;
use App\Models\Staff;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_date',
        'week_ending',
        'classroom_id',
        'subject_id',
        'student_id',
        'staff_id',
        'assessment_type',
        'score',
        'out_of',
        'score_percent',
        'remarks',
        'academic_group',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'week_ending' => 'date',
        'score' => 'decimal:2',
        'out_of' => 'decimal:2',
        'score_percent' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function (self $assessment) {
            if ($assessment->score !== null && $assessment->out_of) {
                $assessment->score_percent = round(($assessment->score / $assessment->out_of) * 100, 2);
            }
        });
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

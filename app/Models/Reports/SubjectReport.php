<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Staff;

class SubjectReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_ending',
        'campus',
        'subject_id',
        'staff_id',
        'classroom_id',
        'topics_covered',
        'syllabus_status',
        'strong_percent',
        'average_percent',
        'struggling_percent',
        'homework_given',
        'test_done',
        'marking_done',
        'main_challenge',
        'support_needed',
        'academic_group',
    ];

    protected $casts = [
        'week_ending' => 'date',
        'homework_given' => 'boolean',
        'test_done' => 'boolean',
        'marking_done' => 'boolean',
        'strong_percent' => 'decimal:2',
        'average_percent' => 'decimal:2',
        'struggling_percent' => 'decimal:2',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

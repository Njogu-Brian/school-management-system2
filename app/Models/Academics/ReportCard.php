<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'student_id','academic_year_id','term_id','classroom_id','stream_id',
        'pdf_path','published_at','published_by','locked_at','public_token',
        'summary','career_interest','talent_noticed',
        'teacher_remark','headteacher_remark',
        // CBC fields
        'performance_summary','core_competencies','learning_areas_performance',
        'cat_breakdown','portfolio_summary','co_curricular',
        'personal_social_dev','attendance_summary','overall_performance_level_id',
        'student_self_assessment','next_term_goals','parent_feedback','upi'
    ];

    protected $casts = [
        'published_at'      => 'datetime',
        'locked_at'         => 'datetime',
        'summary'           => 'string',
        'career_interest'   => 'string',
        'talent_noticed'    => 'string',
        'teacher_remark'    => 'string',
        'headteacher_remark'=> 'string',
        // CBC casts
        'performance_summary' => 'array',
        'core_competencies' => 'array',
        'learning_areas_performance' => 'array',
        'cat_breakdown' => 'array',
        'portfolio_summary' => 'array',
        'co_curricular' => 'array',
        'personal_social_dev' => 'array',
        'attendance_summary' => 'array',
    ];

    public function student() { return $this->belongsTo(\App\Models\Student::class); }
    public function academicYear() { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function term() { return $this->belongsTo(\App\Models\Term::class); }
    public function classroom() { return $this->belongsTo(\App\Models\Academics\Classroom::class); }
    public function stream() { return $this->belongsTo(\App\Models\Academics\Stream::class); }
    public function publisher() { return $this->belongsTo(\App\Models\Staff::class,'published_by'); }

    public function marks() {
        return $this->hasMany(ExamMark::class,'student_id','student_id')
            ->whereHas('exam', fn($q) => $q
                ->where('academic_year_id',$this->academic_year_id)
                ->where('term_id',$this->term_id));
    }

    public function skills() {
        return $this->hasMany(ReportCardSkill::class);
    }

    public function overallPerformanceLevel()
    {
        return $this->belongsTo(CBCPerformanceLevel::class, 'overall_performance_level_id');
    }
}

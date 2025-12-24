<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Academics\CBCPerformanceLevel;
use App\Models\Academics\PortfolioAssessment;

class ExamMark extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'exam_id','student_id','subject_id','teacher_id',
        'score_raw','score_moderated',
        'opener_score','midterm_score','endterm_score',
        'rubrics','grade_label','pl_level',
        'subject_remark','remark','status','audit',
        // CBC fields
        'assessment_method','cat_number','performance_level_id',
        'competency_scores','portfolio_assessment_id',
        // Advanced exam features
        'component_scores',
        'descriptor',
    ];

    protected $casts = [
        'rubrics' => 'array',
        'audit'   => 'array',
        'competency_scores' => 'array',
        'component_scores' => 'array',
        'cat_number' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function student() { return $this->belongsTo(\App\Models\Student::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(\App\Models\Staff::class,'teacher_id'); }
    public function performanceLevel() { return $this->belongsTo(CBCPerformanceLevel::class, 'performance_level_id'); }
    public function portfolioAssessment() { return $this->belongsTo(PortfolioAssessment::class, 'portfolio_assessment_id'); }
    
    protected static function booted() {
        static::creating(function($m){
            $m->entered_by = auth()->id();
            $m->updated_by = auth()->id();
        });
        static::updating(function($m){
            $m->updated_by = auth()->id();
        });
    }

}

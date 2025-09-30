<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'student_id','academic_year_id','term_id','classroom_id','stream_id',
        'pdf_path','published_at','published_by','locked_at','summary',
        'career_interest','talent_noticed','teacher_remark','headteacher_remark',
        'public_token'
    ];

    protected $casts = [
        'published_at'=>'datetime',
        'locked_at'=>'datetime',
        'summary'=>'array',
    ];

    public function student()     { return $this->belongsTo(\App\Models\Student::class); }
    public function academicYear(){ return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function term()        { return $this->belongsTo(\App\Models\Term::class); }
    public function classroom()   { return $this->belongsTo(\App\Models\Academics\Classroom::class); }
    public function stream()      { return $this->belongsTo(\App\Models\Academics\Stream::class); }
    public function publisher()   { return $this->belongsTo(\App\Models\Staff::class,'published_by'); }
    public function skills()      { return $this->hasMany(ReportCardSkill::class); }

    public function marks() {
        return $this->hasMany(ExamMark::class,'student_id','student_id')
            ->whereHas('exam', fn($q)=>$q
                ->where('academic_year_id',$this->academic_year_id)
                ->where('term_id',$this->term_id));
    }
}

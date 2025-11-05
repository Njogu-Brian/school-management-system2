<?php

// app/Models/Academics/StudentSkillGrade.php
namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class StudentSkillGrade extends Model
{
    protected $fillable = [
        'student_id','term_id','academic_year_id',
        'report_card_skill_id','grade','comment','entered_by','updated_by'
    ];

    public function student(){ return $this->belongsTo(\App\Models\Student::class); }
    public function term(){ return $this->belongsTo(\App\Models\Term::class); }
    public function academicYear(){ return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function skill(){ return $this->belongsTo(ReportCardSkill::class, 'report_card_skill_id'); }
}

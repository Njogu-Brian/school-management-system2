<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\AcademicYear;
use App\Models\Term;

class ExamGroup extends Model
{
    protected $fillable = [
        'name','exam_type_id','academic_year_id','term_id','description','is_active','created_by'
    ];

    public function type(){ return $this->belongsTo(ExamType::class,'exam_type_id'); }
    public function academicYear(){ return $this->belongsTo(AcademicYear::class); }
    public function term(){ return $this->belongsTo(Term::class); }
    public function exams(){ return $this->hasMany(Exam::class, 'exam_group_id'); }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStatement extends Model
{
    protected $fillable = ['student_id', 'term_id', 'academic_year_id', 'generated_at'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function year()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}

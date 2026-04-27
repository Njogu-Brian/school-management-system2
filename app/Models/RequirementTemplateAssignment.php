<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequirementTemplateAssignment extends Model
{
    protected $fillable = [
        'requirement_template_id',
        'academic_year_id',
        'term_id',
        'classroom_id',
        'student_type',
        'brand',
        'quantity_per_student',
        'unit',
        'notes',
        'leave_with_teacher',
        'is_verification_only',
        'is_active',
    ];

    protected $casts = [
        'quantity_per_student' => 'decimal:2',
        'leave_with_teacher' => 'boolean',
        'is_verification_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(RequirementTemplate::class, 'requirement_template_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }
}


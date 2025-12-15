<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequirementTemplate extends Model
{
    protected $fillable = [
        'requirement_type_id', 'classroom_id', 'academic_year_id', 'term_id',
        'brand', 'quantity_per_student', 'unit', 'student_type',
        'leave_with_teacher', 'is_verification_only', 'notes', 'is_active',
        'pos_product_id', 'is_available_in_shop'
    ];

    protected $casts = [
        'quantity_per_student' => 'decimal:2',
        'leave_with_teacher' => 'boolean',
        'is_verification_only' => 'boolean',
        'is_active' => 'boolean',
        'is_available_in_shop' => 'boolean',
    ];

    public function requirementType()
    {
        return $this->belongsTo(RequirementType::class);
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function studentRequirements()
    {
        return $this->hasMany(StudentRequirement::class);
    }

    public function posProduct()
    {
        return $this->belongsTo(\App\Models\Pos\Product::class, 'pos_product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(\App\Models\Pos\OrderItem::class, 'requirement_template_id');
    }
}

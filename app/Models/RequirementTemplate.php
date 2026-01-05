<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequirementTemplate extends Model
{
    protected $fillable = [
        'requirement_type_id', 'classroom_id', 'academic_year_id', 'term_id',
        'brand', 'quantity_per_student', 'unit', 'student_type',
        'leave_with_teacher', 'custody_type', 'is_verification_only', 'notes', 'is_active',
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

    /**
     * Get all classrooms assigned to this requirement template
     * Supports both single classroom (via classroom_id) and multiple (via pivot)
     */
    public function classrooms()
    {
        return $this->belongsToMany(
            \App\Models\Academics\Classroom::class,
            'requirement_template_classrooms',
            'requirement_template_id',
            'classroom_id'
        )->withTimestamps();
    }

    /**
     * Get all classrooms (including the primary one if set)
     */
    public function allClassrooms()
    {
        $classrooms = $this->classrooms;
        
        // Include primary classroom if set and not already in the list
        if ($this->classroom_id) {
            $primaryClassroom = \App\Models\Academics\Classroom::find($this->classroom_id);
            if ($primaryClassroom && !$classrooms->contains('id', $primaryClassroom->id)) {
                $classrooms->push($primaryClassroom);
            }
        }
        
        return $classrooms;
    }

    /**
     * Check if this is a school custody item
     */
    public function isSchoolCustody(): bool
    {
        return $this->custody_type === 'school_custody';
    }

    /**
     * Check if this is a parent custody item
     */
    public function isParentCustody(): bool
    {
        return $this->custody_type === 'parent_custody';
    }

    /**
     * Replicate requirement to other classes
     */
    public function replicateToClasses(array $classroomIds): array
    {
        $replicated = [];
        
        foreach ($classroomIds as $classroomId) {
            $replica = $this->replicate();
            $replica->classroom_id = $classroomId;
            $replica->save();
            $replicated[] = $replica;
        }
        
        return $replicated;
    }
}

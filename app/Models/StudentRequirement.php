<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRequirement extends Model
{
    protected $fillable = [
        'student_id', 'requirement_template_id', 'academic_year_id', 'term_id',
        'collected_by', 'quantity_required', 'quantity_collected', 'quantity_missing',
        'status', 'collected_at', 'notes', 'notified_parent'
    ];

    protected $casts = [
        'quantity_required' => 'decimal:2',
        'quantity_collected' => 'decimal:2',
        'quantity_missing' => 'decimal:2',
        'collected_at' => 'datetime',
        'notified_parent' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requirementTemplate()
    {
        return $this->belongsTo(RequirementTemplate::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }

    public function inventoryTransaction()
    {
        return $this->hasOne(InventoryTransaction::class);
    }

    public function updateStatus()
    {
        if ($this->quantity_collected >= $this->quantity_required) {
            $this->status = 'complete';
            $this->quantity_missing = 0;
        } elseif ($this->quantity_collected > 0) {
            $this->status = 'partial';
            $this->quantity_missing = $this->quantity_required - $this->quantity_collected;
        } else {
            $this->status = 'pending';
            $this->quantity_missing = $this->quantity_required;
        }
        $this->save();
    }
}

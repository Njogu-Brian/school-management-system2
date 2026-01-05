<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemReceipt extends Model
{
    protected $fillable = [
        'student_requirement_id', 'student_id', 'classroom_id',
        'received_by', 'quantity_received', 'receipt_status', 'notes', 'received_at'
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function studentRequirement()
    {
        return $this->belongsTo(StudentRequirement::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    /**
     * Check if this receipt is for a school custody item
     */
    public function isSchoolCustody(): bool
    {
        $template = $this->studentRequirement->requirementTemplate ?? null;
        return $template && $template->custody_type === 'school_custody';
    }
}


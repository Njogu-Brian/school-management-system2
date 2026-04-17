<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTermFeeClearance extends Model
{
    protected $fillable = [
        'student_id',
        'term_id',
        'status',
        'computed_at',
        'percentage_paid',
        'minimum_percentage',
        'has_valid_payment_plan',
        'payment_plan_id',
        'payment_plan_status',
        'final_clearance_deadline',
        'reason_code',
        'meta',
    ];

    protected $casts = [
        'computed_at' => 'datetime',
        'percentage_paid' => 'decimal:2',
        'minimum_percentage' => 'decimal:2',
        'has_valid_payment_plan' => 'boolean',
        'final_clearance_deadline' => 'date',
        'meta' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function paymentPlan()
    {
        return $this->belongsTo(FeePaymentPlan::class, 'payment_plan_id');
    }
}


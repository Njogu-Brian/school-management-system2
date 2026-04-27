<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StudentTermFeeClearance extends Model
{
    /**
     * Clearance reasons where no term threshold deadline applies (no outstanding fee obligation).
     *
     * @var list<string>
     */
    public const REASONS_NO_CLEARANCE_DEADLINE = ['fully_paid', 'no_fees'];

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

    /**
     * Deadline shown to users: hidden when the student has no remaining fee obligation for this term.
     */
    public function displayFinalClearanceDeadline(): ?Carbon
    {
        if (in_array((string) $this->reason_code, self::REASONS_NO_CLEARANCE_DEADLINE, true)) {
            return null;
        }

        return $this->final_clearance_deadline;
    }
}


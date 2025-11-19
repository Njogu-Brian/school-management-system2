<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePaymentPlanInstallment extends Model
{
    protected $fillable = [
        'payment_plan_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_date',
        'paid_amount',
        'status',
        'payment_id',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function paymentPlan()
    {
        return $this->belongsTo(FeePaymentPlan::class, 'payment_plan_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}

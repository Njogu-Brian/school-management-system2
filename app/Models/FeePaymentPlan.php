<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePaymentPlan extends Model
{
    protected $fillable = [
        'student_id',
        'invoice_id',
        'total_amount',
        'installment_count',
        'installment_amount',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function installments()
    {
        return $this->hasMany(FeePaymentPlanInstallment::class, 'payment_plan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

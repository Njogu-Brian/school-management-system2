<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeReminder extends Model
{
    protected $fillable = [
        'student_id',
        'invoice_id',
        'payment_plan_id',
        'payment_plan_installment_id',
        'term_id',
        'fee_reminder_type',
        'reason_code',
        'channels',
        'hashed_id',
        'channel',
        'status',
        'outstanding_amount',
        'due_date',
        'days_before_due',
        'reminder_rule',
        'sent_at',
        'message',
        'error_message',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'outstanding_amount' => 'decimal:2',
        'channels' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentPlan()
    {
        return $this->belongsTo(FeePaymentPlan::class);
    }

    public function paymentPlanInstallment()
    {
        return $this->belongsTo(FeePaymentPlanInstallment::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            $hash = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($reminder) {
            if (!$reminder->hashed_id) {
                $reminder->hashed_id = self::generateHashedId();
            }
        });
    }
}

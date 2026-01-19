<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MpesaC2BTransaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mpesa_c2b_transactions';

    protected $fillable = [
        'transaction_type',
        'trans_id',
        'trans_time',
        'trans_amount',
        'business_short_code',
        'bill_ref_number',
        'invoice_number',
        'org_account_balance',
        'third_party_trans_id',
        'msisdn',
        'first_name',
        'middle_name',
        'last_name',
        'student_id',
        'invoice_id',
        'payment_id',
        'allocation_status',
        'allocated_amount',
        'unallocated_amount',
        'matching_suggestions',
        'match_confidence',
        'match_reason',
        'is_duplicate',
        'duplicate_of',
        'status',
        'notes',
        'processed_by',
        'processed_at',
        'raw_data',
    ];

    protected $casts = [
        'trans_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'match_confidence' => 'integer',
        'is_duplicate' => 'boolean',
        'matching_suggestions' => 'array',
        'raw_data' => 'array',
        'processed_at' => 'datetime',
        'trans_time' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function duplicateOf()
    {
        return $this->belongsTo(MpesaC2BTransaction::class, 'duplicate_of');
    }

    /**
     * Scopes
     */
    public function scopeUnallocated($query)
    {
        return $query->where('allocation_status', 'unallocated')
                     ->where('status', '!=', 'ignored')
                     ->where('is_duplicate', false);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('is_duplicate', false);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute()
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);
        
        return implode(' ', $parts) ?: 'Unknown';
    }

    public function getFormattedPhoneAttribute()
    {
        $phone = $this->msisdn;
        if (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            return '0' . substr($phone, 3);
        }
        return $phone;
    }

    public function getIsFullyAllocatedAttribute()
    {
        return $this->allocated_amount >= $this->trans_amount;
    }

    public function getRemainingAmountAttribute()
    {
        return $this->trans_amount - $this->allocated_amount;
    }

    /**
     * Helper methods
     */
    public function markAsProcessed($userId = null)
    {
        $this->update([
            'status' => 'processed',
            'processed_by' => $userId ?? auth()->id(),
            'processed_at' => now(),
        ]);
    }

    public function markAsDuplicate($originalTransactionId)
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_of' => $originalTransactionId,
            'status' => 'ignored',
            'allocation_status' => 'duplicate',
        ]);
    }

    public function allocate(Student $student, ?Invoice $invoice = null, Payment $payment, $amount)
    {
        $this->update([
            'student_id' => $student->id,
            'invoice_id' => $invoice?->id,
            'payment_id' => $payment->id,
            'allocated_amount' => $this->allocated_amount + $amount,
            'allocation_status' => 'manually_allocated',
            'status' => 'processed',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);
    }

    public function autoMatch(Student $student, $confidence, $reason)
    {
        $this->update([
            'student_id' => $student->id,
            'match_confidence' => $confidence,
            'match_reason' => $reason,
            'allocation_status' => $confidence >= 80 ? 'auto_matched' : 'unallocated',
        ]);
    }

    public function storeSuggestions(array $suggestions)
    {
        $this->update([
            'matching_suggestions' => $suggestions,
        ]);
    }

    /**
     * Check if this transaction is a duplicate
     */
    public function checkForDuplicate()
    {
        $duplicate = static::where('trans_id', $this->trans_id)
            ->where('id', '!=', $this->id)
            ->first();

        if ($duplicate) {
            $this->markAsDuplicate($duplicate->id);
            return true;
        }

        // Also check for same amount, phone, and time (within 1 minute)
        $duplicate = static::where('msisdn', $this->msisdn)
            ->where('trans_amount', $this->trans_amount)
            ->whereBetween('trans_time', [
                $this->trans_time->subMinute(),
                $this->trans_time->addMinute(),
            ])
            ->where('id', '!=', $this->id)
            ->first();

        if ($duplicate) {
            $this->markAsDuplicate($duplicate->id);
            return true;
        }

        return false;
    }
}

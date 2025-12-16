<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeConcession extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'family_id',
        'votehead_id',
        'invoice_id',
        'discount_template_id',
        'term',
        'year',
        'academic_year_id',
        'type',
        'discount_type',
        'frequency',
        'scope',
        'value',
        'reason',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'approval_status',
        'rejection_reason',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'term' => 'integer',
        'year' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function discountTemplate(): BelongsTo
    {
        return $this->belongsTo(DiscountTemplate::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Calculate discount amount for a given fee amount
     */
    public function calculateDiscount($feeAmount)
    {
        if ($this->type === 'percentage') {
            return ($feeAmount * $this->value) / 100;
        }
        return min($this->value, $feeAmount); // Fixed amount, but can't exceed fee
    }
}

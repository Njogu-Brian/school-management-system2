<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeConcession extends Model
{
    protected $fillable = [
        'student_id',
        'votehead_id',
        'type',
        'value',
        'reason',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'value' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function votehead()
    {
        return $this->belongsTo(Votehead::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

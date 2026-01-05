<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentThreshold extends Model
{
    protected $fillable = [
        'term_id',
        'student_category_id',
        'minimum_percentage',
        'final_deadline_day',
        'final_deadline_month_offset',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'minimum_percentage' => 'decimal:2',
        'final_deadline_day' => 'integer',
        'final_deadline_month_offset' => 'integer',
        'is_active' => 'boolean',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function studentCategory()
    {
        return $this->belongsTo(StudentCategory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate the final clearance deadline date based on term opening date
     */
    public function calculateFinalDeadlineDate($termOpeningDate): \Carbon\Carbon
    {
        $date = \Carbon\Carbon::parse($termOpeningDate);
        $date->addMonths($this->final_deadline_month_offset);
        $date->day = min($this->final_deadline_day, $date->daysInMonth);
        return $date;
    }
}

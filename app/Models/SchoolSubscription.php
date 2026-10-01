<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolSubscription extends Model
{
    protected $connection = 'control';

    protected $fillable = [
        'school_registry_id',
        'period',
        'amount_due',
        'amount_paid',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(SchoolRegistry::class, 'school_registry_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SchoolPayment::class, 'school_subscription_id');
    }

    public function refreshStatus(): void
    {
        $due = (float) $this->amount_due;
        $paid = (float) $this->amount_paid;

        if ($paid <= 0 && $this->due_date && $this->due_date->isPast()) {
            $this->status = 'overdue';
        } elseif ($paid >= $due && $due > 0) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'open';
        }
        $this->save();
    }
}

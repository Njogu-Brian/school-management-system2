<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Payment;

class BankAccount extends Model
{
    protected $fillable = [
        'name',
        'account_number',
        'bank_name',
        'branch',
        'account_type',
        'is_active',
        'currency',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}


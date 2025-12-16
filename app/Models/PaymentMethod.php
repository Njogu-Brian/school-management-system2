<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\{Payment, BankAccount};

class PaymentMethod extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'requires_reference',
        'is_online',
        'is_active',
        'display_order',
        'description',
        'bank_account_id',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }
}


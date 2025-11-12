<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'calculation_method',
        'default_amount',
        'percentage',
        'is_active',
        'is_statutory',
        'requires_approval',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_statutory' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    public function customDeductions()
    {
        return $this->hasMany(CustomDeduction::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate deduction amount based on method
     */
    public function calculateAmount($baseAmount = 0)
    {
        switch ($this->calculation_method) {
            case 'fixed_amount':
                return $this->default_amount ?? 0;
            
            case 'percentage_of_basic':
                return ($baseAmount * ($this->percentage ?? 0)) / 100;
            
            case 'percentage_of_gross':
                return ($baseAmount * ($this->percentage ?? 0)) / 100;
            
            default:
                return $this->default_amount ?? 0;
        }
    }

    /**
     * Scope: Active deduction types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Non-statutory (custom) deduction types
     */
    public function scopeCustom($query)
    {
        return $query->where('is_statutory', false);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\{FeeCharge, OptionalFee, InvoiceItem, FeeConcession, Student, InvoiceItem as InvoiceItemModel};

class Votehead extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'is_mandatory',
        'charge_type',
        'preferred_term', // For once_annually: which term to charge (1, 2, or 3)
        'is_optional',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_optional' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate code from name if not provided
        static::creating(function ($votehead) {
            if (empty($votehead->code) && !empty($votehead->name)) {
                $votehead->code = static::generateCodeFromName($votehead->name);
            }
        });

        static::updating(function ($votehead) {
            // Update code if name changed and code is empty
            if (empty($votehead->code) && !empty($votehead->name)) {
                $votehead->code = static::generateCodeFromName($votehead->name);
            }
        });
    }

    /**
     * Generate code from name
     */
    protected static function generateCodeFromName(string $name): string
    {
        // Convert to uppercase, replace spaces and special chars with underscores
        $code = strtoupper(trim($name));
        $code = preg_replace('/[^A-Z0-9]+/', '_', $code);
        $code = trim($code, '_');
        
        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (static::where('code', $code)->exists()) {
            $code = $originalCode . '_' . $counter;
            $counter++;
        }
        
        return $code;
    }

    public function feeCharges(): HasMany
    {
        return $this->hasMany(FeeCharge::class);
    }

    public function optionalFees(): HasMany
    {
        return $this->hasMany(OptionalFee::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function feeConcessions(): HasMany
    {
        return $this->hasMany(FeeConcession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_optional', true)->orWhere('is_mandatory', false);
    }

    /**
     * Validate charge type constraints
     */
    public function canChargeForStudent(Student $student, int $year, int $term): bool
    {
        switch ($this->charge_type) {
            case 'once':
                // Once-only fees should ONLY be charged to newly admitted students upon admission
                // Do NOT charge continuing/existing students, even if they haven't been charged before
                $isNewStudent = $student->isNewlyAdmitted($year);
                
                if (!$isNewStudent) {
                    // Existing/continuing student - DO NOT charge once fees
                    // They should have been charged at admission, and if missed, it should be handled manually
                    return false;
                }
                
                // New student - check if already charged (shouldn't happen, but safety check)
                return !InvoiceItemModel::whereHas('invoice', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })->where('votehead_id', $this->id)->exists();
                
            case 'once_annually':
                // Check if already charged in this year
                $alreadyCharged = InvoiceItemModel::whereHas('invoice', function ($q) use ($student, $year) {
                    $q->where('student_id', $student->id)->where('year', $year);
                })->where('votehead_id', $this->id)->exists();
                
                if ($alreadyCharged) {
                    return false;
                }
                
                // If preferred_term is set, only charge in that term
                if ($this->preferred_term !== null) {
                    return $term == $this->preferred_term;
                }
                
                // No preferred term - charge in any term (but only once per year)
                return true;
                
            case 'per_family':
                // Check if already charged for any sibling in family
                if (!$student->family_id) {
                    return true;
                }
                return !InvoiceItemModel::whereHas('invoice.student', function ($q) use ($student) {
                    $q->where('family_id', $student->family_id);
                })->where('votehead_id', $this->id)->exists();
                
            case 'per_student':
            default:
                // Can charge every term
                return true;
        }
    }
}

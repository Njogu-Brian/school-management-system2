<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePaymentPlan extends Model
{
    protected $fillable = [
        'student_id',
        'invoice_id',
        'term_id',
        'academic_year_id',
        'hashed_id',
        'total_amount',
        'installment_count',
        'installment_amount',
        'start_date',
        'end_date',
        'final_clearance_deadline',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'final_clearance_deadline' => 'date',
        'total_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function installments()
    {
        return $this->hasMany(FeePaymentPlanInstallment::class, 'payment_plan_id');
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
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            $hash = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    /**
     * Get route key name - use ID for internal routes
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve route binding - support both ID and hashed_id
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'hashed_id') {
            return $this->where('hashed_id', $value)->firstOrFail();
        }
        
        if (is_numeric($value)) {
            return $this->where('id', $value)->firstOrFail();
        }
        
        return $this->where('hashed_id', $value)->firstOrFail();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($plan) {
            if (!$plan->hashed_id) {
                $plan->hashed_id = self::generateHashedId();
            }
        });
    }
}

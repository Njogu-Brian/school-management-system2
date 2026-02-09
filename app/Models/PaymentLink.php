<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'token',
        'hashed_id',
        'student_id',
        'invoice_id',
        'family_id',
        'amount',
        'currency',
        'description',
        'payment_reference',
        'account_reference',
        'status',
        'expires_at',
        'used_at',
        'payment_id',
        'created_by',
        'max_uses',
        'use_count',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
        'max_uses' => 'integer',
        'use_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($link) {
            if (!$link->token) {
                $link->token = self::generateToken();
            }
            if (!$link->hashed_id) {
                $link->hashed_id = self::generateHashedId();
            }
            if (!$link->payment_reference) {
                $link->payment_reference = 'LINK-' . strtoupper(Str::random(10));
            }
            // Set account_reference based on student and swimming flag (family links have student_id null)
            if ($link->account_reference) {
                return;
            }
            if ($link->student_id && $link->student) {
                $isSwimming = isset($link->metadata['is_swimming']) && $link->metadata['is_swimming'];
                $link->account_reference = $isSwimming 
                    ? 'SWIM-' . $link->student->admission_number 
                    : $link->student->admission_number;
            } elseif ($link->family_id) {
                $link->account_reference = 'FAM-' . $link->family_id;
            }
        });
    }

    /**
     * Generate unique token for payment link
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(16);
        } while (self::where('token', $token)->exists());
        
        return $token;
    }

    /**
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            $hash = Str::random(12);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    /**
     * Get the student for this payment link
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the invoice for this payment link
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the family for this payment link
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the payment made through this link
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who created this link
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if link is active and can be used
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->markAsExpired();
            return false;
        }

        if ($this->use_count >= $this->max_uses) {
            $this->markAsUsed();
            return false;
        }

        return true;
    }

    /**
     * Check if link is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Mark link as used
     */
    public function markAsUsed(): void
    {
        $this->status = 'used';
        $this->used_at = now();
        $this->save();
    }

    /**
     * Mark link as expired
     */
    public function markAsExpired(): void
    {
        $this->status = 'expired';
        $this->save();
    }

    /**
     * Increment use count
     */
    public function incrementUseCount(): void
    {
        $this->increment('use_count');
        
        if ($this->use_count >= $this->max_uses) {
            $this->markAsUsed();
        }
    }

    /**
     * Get the full payment URL
     */
    public function getPaymentUrl(): string
    {
        return route('payment.link.show', $this->hashed_id);
    }

    /**
     * Get the short payment URL (for SMS)
     */
    public function getShortUrl(): string
    {
        // If you have a URL shortener service, use it here
        // For now, return a shortened version using token
        return url('/pay/' . $this->token);
    }

    /**
     * Get or create an active family payment link (student_id null, family_id set).
     * Reuses an existing active link for the family. Used by receipt pay-now and payment plan.
     */
    public static function getOrCreateFamilyLink(int $familyId, $createdBy = null, string $source = 'unified'): self
    {
        $existing = self::where('family_id', $familyId)
            ->whereNull('student_id')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereRaw('use_count < max_uses')
            ->orderByDesc('expires_at')
            ->first();
        if ($existing) {
            return $existing;
        }
        return self::create([
            'student_id' => null,
            'invoice_id' => null,
            'family_id' => $familyId,
            'amount' => 0,
            'currency' => 'KES',
            'description' => 'Pay school fees - all children',
            'status' => 'active',
            'expires_at' => now()->addDays(90),
            'max_uses' => 999,
            'created_by' => $createdBy,
            'metadata' => ['source' => $source],
        ]);
    }

    /**
     * Scope: Active links
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->whereRaw('use_count < max_uses');
    }

    /**
     * Scope: Expired links
     */
    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'expired')
              ->orWhere(function($q2) {
                  $q2->where('expires_at', '<=', now())
                     ->where('status', 'active');
              });
        });
    }

    /**
     * Resolve route binding - support both hashed_id and token
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'token') {
            return $this->where('token', $value)->firstOrFail();
        }
        
        if ($field === 'hashed_id' || strlen($value) > 10) {
            return $this->where('hashed_id', $value)->firstOrFail();
        }
        
        // Default to ID for admin routes
        return $this->where('id', $value)->firstOrFail();
    }
}


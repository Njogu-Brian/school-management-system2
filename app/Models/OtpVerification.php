<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpVerification extends Model
{
    protected $fillable = [
        'identifier',
        'otp_code',
        'purpose',
        'verified',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if OTP is valid (not expired and not verified)
     */
    public function isValid(): bool
    {
        return !$this->verified && $this->expires_at->isFuture();
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Scope to get valid OTPs
     */
    public function scopeValid($query)
    {
        return $query->where('verified', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get OTPs by identifier and purpose
     */
    public function scopeForIdentifier($query, string $identifier, string $purpose = 'login')
    {
        return $query->where('identifier', $identifier)
            ->where('purpose', $purpose);
    }
}

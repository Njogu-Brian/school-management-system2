<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffRegistration extends Model
{
    use Concerns\NormalizesNameAttributes;

    protected static array $sentenceCaseNameAttributes = [
        'first_name',
        'middle_name',
        'last_name',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'first_name', 'middle_name', 'last_name',
        'gender', 'date_of_birth', 'marital_status',
        'id_number', 'personal_email', 'phone_number', 'emergency_contact_phone',
        'kra_pin', 'nssf', 'nhif',
        'bank_name', 'bank_branch', 'bank_account',
        'status', 'rejection_reason',
        'reviewed_by', 'reviewed_at', 'staff_id', 'ip_address',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter(fn ($part) => filled($part))
            ->implode(' '));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}

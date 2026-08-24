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
        'gender', 'date_of_birth', 'marital_status', 'residential_address',
        'id_number', 'personal_email', 'phone_number', 'photo',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
        'kra_pin', 'nssf', 'nhif',
        'bank_name', 'bank_branch', 'bank_account', 'payment_method',
        'department_id', 'job_title_id', 'staff_category_id',
        'hire_date', 'employment_type', 'contract_start_date', 'contract_end_date',
        'max_lessons_per_week',
        'status', 'rejection_reason',
        'reviewed_by', 'reviewed_at', 'staff_id', 'ip_address',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StaffCategory::class, 'staff_category_id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        $disk = config('filesystems.public_disk', 'public');
        try {
            return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->photo);
        } catch (\Throwable $e) {
            return null;
        }
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

<?php

namespace App\Models\Admissions;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdmissionApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_ASSESSMENT_BOOKED = 'assessment_booked';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ENROLLED = 'enrolled';

    protected $fillable = [
        'application_no',
        'parent_name',
        'phone',
        'email',
        'child_name',
        'dob',
        'gender',
        'age',
        'desired_class',
        'previous_school',
        'medical_notes',
        'special_needs',
        'status',
        'source',
        'assigned_staff',
        'assessment_date',
        'admission_notes',
        'draft_token',
        'current_step',
        'form_progress',
        'student_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'dob' => 'date',
        'assessment_date' => 'datetime',
        'reviewed_at' => 'datetime',
        'form_progress' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AdmissionApplication $app) {
            if (empty($app->application_no)) {
                $app->application_no = static::generateApplicationNo();
            }
            if (empty($app->draft_token)) {
                $app->draft_token = (string) Str::uuid();
            }
        });
    }

    public static function generateApplicationNo(): string
    {
        $year = now()->format('Y');
        $last = static::query()
            ->where('application_no', 'like', "APP-{$year}-%")
            ->orderByDesc('id')
            ->value('application_no');

        $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

        return sprintf('APP-%s-%05d', $year, $seq);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONTACTED,
            self::STATUS_ASSESSMENT_BOOKED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_ENROLLED,
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AdmissionDocument::class, 'application_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function childNameParts(): array
    {
        $parts = preg_split('/\s+/', trim($this->child_name), -1, PREG_SPLIT_NO_EMPTY) ?: ['Child'];

        return [
            'first_name' => $parts[0],
            'middle_name' => count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null,
            'last_name' => count($parts) > 1 ? $parts[count($parts) - 1] : 'Applicant',
        ];
    }
}

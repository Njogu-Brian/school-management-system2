<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ParentInfo extends Model
{
    use \App\Models\Concerns\NormalizesNameAttributes;

    protected static array $sentenceCaseNameAttributes = [
        'father_name',
        'mother_name',
        'guardian_name',
        'primary_contact_person',
    ];

    protected $table = 'parent_info';

    protected $fillable = [
        'father_name', 'father_phone', 'father_whatsapp', 'father_email', 'father_id_number',
        'mother_name', 'mother_phone', 'mother_whatsapp', 'mother_email', 'mother_id_number',
        'guardian_name', 'guardian_phone', 'guardian_whatsapp', 'guardian_email',
        'guardian_relationship', 'marital_status',
        'father_phone_country_code', 'mother_phone_country_code', 'guardian_phone_country_code',
        'father_whatsapp_country_code', 'mother_whatsapp_country_code',
        'father_id_document', 'mother_id_document',
        // Extended parent info
        'father_occupation', 'father_employer', 'father_work_address', 'father_education_level',
        'mother_occupation', 'mother_employer', 'mother_work_address', 'mother_education_level',
        'guardian_occupation', 'guardian_employer', 'guardian_work_address', 'guardian_education_level',
        'family_income_bracket', 'primary_contact_person', 'communication_preference', 'language_preference',
        'school_notifications_muted_parent',
    ];

    /**
     * Normalize empty string to null for school_notifications_muted_parent.
     */
    public function setSchoolNotificationsMutedParentAttribute($value): void
    {
        $v = $value === '' ? null : $value;
        $this->attributes['school_notifications_muted_parent'] = $v;
    }

    /**
     * Father/mother SMS numbers for automated school notifications (guardian excluded).
     *
     * @return list<string>
     */
    public function schoolNotificationSmsPhones(): array
    {
        $father = $this->father_phone ?: null;
        $mother = $this->mother_phone ?: null;
        if ($this->school_notifications_muted_parent === 'father') {
            $father = null;
        } elseif ($this->school_notifications_muted_parent === 'mother') {
            $mother = null;
        }

        return array_values(array_unique(array_filter([$father, $mother])));
    }

    /**
     * @return list<string>
     */
    public function schoolNotificationEmails(): array
    {
        $father = $this->father_email ?: null;
        $mother = $this->mother_email ?: null;
        if ($this->school_notifications_muted_parent === 'father') {
            $father = null;
        } elseif ($this->school_notifications_muted_parent === 'mother') {
            $mother = null;
        }

        return array_values(array_unique(array_filter([$father, $mother])));
    }

    /**
     * WhatsApp numbers for school notifications (uses WhatsApp field, else phone per parent).
     *
     * @return list<string>
     */
    public function schoolNotificationWhatsAppNumbers(): array
    {
        $father = ! empty($this->father_whatsapp) ? $this->father_whatsapp : ($this->father_phone ?: null);
        $mother = ! empty($this->mother_whatsapp) ? $this->mother_whatsapp : ($this->mother_phone ?: null);
        if ($this->school_notifications_muted_parent === 'father') {
            $father = null;
        } elseif ($this->school_notifications_muted_parent === 'mother') {
            $mother = null;
        }

        return array_values(array_unique(array_filter([$father, $mother])));
    }

    /**
     * Ensure at most one parent is muted and the other has at least one contact (phone, WhatsApp, or email).
     *
     * @param  array<string, mixed>  $parentRow  Attributes after the intended save (father_*, mother_*).
     */
    public static function validateSchoolNotificationMute(?string $muted, array $parentRow): void
    {
        if ($muted === null || $muted === '') {
            return;
        }
        if (! in_array($muted, ['father', 'mother'], true)) {
            throw ValidationException::withMessages([
                'school_notifications_muted_parent' => ['Invalid selection.'],
            ]);
        }
        $other = $muted === 'father' ? 'mother' : 'father';
        if (! self::parentSlotHasReachableContact($parentRow, $other)) {
            throw ValidationException::withMessages([
                'school_notifications_muted_parent' => [
                    $muted === 'father'
                        ? 'Mother must have at least one phone, WhatsApp, or email before father can be excluded from school notifications.'
                        : 'Father must have at least one phone, WhatsApp, or email before mother can be excluded from school notifications.',
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $a
     */
    protected static function parentSlotHasReachableContact(array $a, string $slot): bool
    {
        if ($slot === 'father') {
            return filled($a['father_phone'] ?? null) || filled($a['father_email'] ?? null) || filled($a['father_whatsapp'] ?? null);
        }

        return filled($a['mother_phone'] ?? null) || filled($a['mother_email'] ?? null) || filled($a['mother_whatsapp'] ?? null);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public function getPrimaryContactNameAttribute(): ?string
    {
        return $this->father_name
            ?? $this->mother_name
            ?? $this->guardian_name;
    }

    public function getPrimaryContactPhoneAttribute(): ?string
    {
        return $this->father_phone
            ?? $this->mother_phone
            ?? $this->guardian_phone;
    }

    public function getPrimaryContactEmailAttribute(): ?string
    {
        return $this->father_email
            ?? $this->mother_email
            ?? $this->guardian_email;
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}

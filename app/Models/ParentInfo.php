<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ParentInfo extends Model
{
    use \App\Models\Concerns\NormalizesNameAttributes;

    protected static array $sentenceCaseNameAttributes = [
        'father_name',
        'father_first_name',
        'father_middle_name',
        'father_last_name',
        'mother_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_last_name',
        'guardian_name',
        'guardian_first_name',
        'guardian_middle_name',
        'guardian_last_name',
        'primary_contact_person',
    ];

    protected $table = 'parent_info';

    protected $fillable = [
        'father_name', 'father_first_name', 'father_middle_name', 'father_last_name',
        'father_phone', 'father_whatsapp', 'father_email', 'father_id_number', 'father_id_type',
        'father_country_of_residence',
        'mother_name', 'mother_first_name', 'mother_middle_name', 'mother_last_name',
        'mother_phone', 'mother_whatsapp', 'mother_email', 'mother_id_number', 'mother_id_type',
        'mother_country_of_residence',
        'guardian_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_last_name',
        'guardian_phone', 'guardian_whatsapp', 'guardian_email', 'guardian_id_number',
        'guardian_id_type', 'guardian_country_of_residence',
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
     * Father/mother SMS recipients for automated school notifications (guardian excluded).
     *
     * @return list<array{slot:string, name:?string, phone:string}>
     */
    public function schoolNotificationSmsRecipients(): array
    {
        $out = [];

        if ($this->school_notifications_muted_parent !== 'father' && filled($this->father_phone)) {
            $out[] = ['slot' => 'father', 'name' => $this->father_name ?: null, 'phone' => $this->father_phone];
        }
        if ($this->school_notifications_muted_parent !== 'mother' && filled($this->mother_phone)) {
            $out[] = ['slot' => 'mother', 'name' => $this->mother_name ?: null, 'phone' => $this->mother_phone];
        }

        // De-dupe by phone, keep first entry's name/slot
        $seen = [];
        $unique = [];
        foreach ($out as $r) {
            if (isset($seen[$r['phone']])) continue;
            $seen[$r['phone']] = true;
            $unique[] = $r;
        }

        return $unique;
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
     * @return list<array{slot:string, name:?string, email:string}>
     */
    public function schoolNotificationEmailRecipients(): array
    {
        $out = [];

        if ($this->school_notifications_muted_parent !== 'father' && filled($this->father_email)) {
            $out[] = ['slot' => 'father', 'name' => $this->father_name ?: null, 'email' => $this->father_email];
        }
        if ($this->school_notifications_muted_parent !== 'mother' && filled($this->mother_email)) {
            $out[] = ['slot' => 'mother', 'name' => $this->mother_name ?: null, 'email' => $this->mother_email];
        }

        $seen = [];
        $unique = [];
        foreach ($out as $r) {
            if (isset($seen[$r['email']])) continue;
            $seen[$r['email']] = true;
            $unique[] = $r;
        }

        return $unique;
    }

    /**
     * WhatsApp numbers for school notifications.
     *
     * Uses the WhatsApp field when set, and also the phone if it is a different
     * number (a parent abroad may have WhatsApp on one SIM and a Kenyan phone).
     *
     * @return list<string>
     */
    public function schoolNotificationWhatsAppNumbers(): array
    {
        return array_values(array_map(
            fn (array $r) => $r['phone'],
            $this->schoolNotificationWhatsAppRecipients()
        ));
    }

    /**
     * @return list<array{slot:string, name:?string, phone:string}>
     */
    public function schoolNotificationWhatsAppRecipients(): array
    {
        $slots = [
            'father' => [
                'muted' => $this->school_notifications_muted_parent === 'father',
                'name' => $this->father_name ?: null,
                'numbers' => [$this->father_whatsapp, $this->father_phone],
            ],
            'mother' => [
                'muted' => $this->school_notifications_muted_parent === 'mother',
                'name' => $this->mother_name ?: null,
                'numbers' => [$this->mother_whatsapp, $this->mother_phone],
            ],
        ];

        $out = [];
        $seen = [];
        foreach ($slots as $slot => $data) {
            if ($data['muted']) {
                continue;
            }
            foreach ($data['numbers'] as $raw) {
                $phone = trim((string) ($raw ?? ''));
                if ($phone === '') {
                    continue;
                }
                $key = preg_replace('/\D+/', '', $phone) ?: $phone;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = ['slot' => $slot, 'name' => $data['name'], 'phone' => $phone];
            }
        }

        return $out;
    }

    /**
     * Resolve father/mother recipient row for a contact (respects do-not-notify preference).
     *
     * @return array{slot:string, name:?string, phone?:string, email?:string}|null
     */
    public function findSchoolNotificationRecipientByContact(string $channel, string $contact): ?array
    {
        $needle = normalize_contact_for_parent_match($contact);
        if ($needle === '') {
            return null;
        }

        $recipients = match ($channel) {
            'email' => $this->schoolNotificationEmailRecipients(),
            'whatsapp' => $this->schoolNotificationWhatsAppRecipients(),
            default => $this->schoolNotificationSmsRecipients(),
        };

        foreach ($recipients as $r) {
            $field = $channel === 'email' ? 'email' : 'phone';
            $candidate = normalize_contact_for_parent_match($r[$field] ?? '');
            if ($candidate !== '' && $candidate === $needle) {
                return $r;
            }
        }

        return null;
    }

    public function contactAllowedForSchoolNotification(string $channel, string $contact): bool
    {
        return $this->findSchoolNotificationRecipientByContact($channel, $contact) !== null;
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

    public static function composeFullName(?string $first, ?string $middle, ?string $last): ?string
    {
        $parts = array_values(array_filter([
            trim((string) $first),
            trim((string) $middle),
            trim((string) $last),
        ], static fn ($part) => $part !== ''));

        return $parts === [] ? null : implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    public static function mergePersonNamesFromInput(array $input, string $slot): array
    {
        $first = trim((string) ($input["{$slot}_first_name"] ?? ''));
        $middle = trim((string) ($input["{$slot}_middle_name"] ?? ''));
        $last = trim((string) ($input["{$slot}_last_name"] ?? ''));
        $legacy = trim((string) ($input["{$slot}_name"] ?? ''));

        if ($first === '' && $middle === '' && $last === '') {
            return [
                "{$slot}_first_name" => null,
                "{$slot}_middle_name" => null,
                "{$slot}_last_name" => null,
                "{$slot}_name" => $legacy !== '' ? $legacy : null,
            ];
        }

        return [
            "{$slot}_first_name" => $first !== '' ? $first : null,
            "{$slot}_middle_name" => $middle !== '' ? $middle : null,
            "{$slot}_last_name" => $last !== '' ? $last : null,
            "{$slot}_name" => self::composeFullName($first, $middle, $last),
        ];
    }

    public static function resolvedSlotName($input, string $slot): ?string
    {
        $data = is_array($input) ? $input : $input->all();
        $merged = self::mergePersonNamesFromInput($data, $slot);

        return $merged["{$slot}_name"] ?? null;
    }

    /**
     * @return array{first: string, middle: string, last: string}
     */
    public function formNameParts(string $slot): array
    {
        $first = trim((string) ($this->{"{$slot}_first_name"} ?? ''));
        $middle = trim((string) ($this->{"{$slot}_middle_name"} ?? ''));
        $last = trim((string) ($this->{"{$slot}_last_name"} ?? ''));
        if ($first !== '' || $middle !== '' || $last !== '') {
            return ['first' => $first, 'middle' => $middle, 'last' => $last];
        }

        return [
            'first' => trim((string) ($this->{"{$slot}_name"} ?? '')),
            'middle' => '',
            'last' => '',
        ];
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

<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

class KemisProfile
{
    /**
     * @param  mixed  $interests
     * @return list<string>|null
     */
    public static function normalizeLearnerInterests($interests, ?string $other = null): ?array
    {
        $items = is_array($interests) ? $interests : [];
        $items = array_values(array_filter(array_map(
            static fn ($v) => trim((string) $v),
            $items
        ), static fn ($v) => $v !== ''));
        $items = array_values(array_filter($items, static fn ($v) => strcasecmp($v, 'Other') !== 0));

        $other = trim((string) $other);
        if ($other !== '') {
            $items[] = $other;
        }

        $items = array_values(array_unique($items));

        return $items === [] ? null : $items;
    }

    public static function normalizeReligion(?string $religion, ?string $other = null): ?string
    {
        $religion = trim((string) $religion);
        $other = trim((string) $other);
        if ($religion === '' && $other === '') {
            return null;
        }
        if (strcasecmp($religion, 'Other') === 0) {
            return $other !== '' ? $other : 'Other';
        }

        return $religion !== '' ? $religion : null;
    }

    /**
     * @return array{selected: string, other: string}
     */
    public static function religionFormState(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return ['selected' => '', 'other' => ''];
        }
        $known = config('kemis.religions', []);
        if (in_array($stored, $known, true)) {
            return ['selected' => $stored, 'other' => ''];
        }

        return ['selected' => 'Other', 'other' => $stored];
    }

    /**
     * @param  mixed  $stored
     * @return array{selected: list<string>, other: string}
     */
    public static function interestsFormState($stored): array
    {
        $items = is_array($stored) ? $stored : [];
        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);
            $items = is_array($decoded) ? $decoded : [];
        }
        $known = config('kemis.learner_interests', []);
        $selected = [];
        $other = '';
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            if (in_array($item, $known, true)) {
                $selected[] = $item;
            } else {
                $other = $item;
            }
        }

        return ['selected' => $selected, 'other' => $other];
    }

    public static function studentKemisValidationRules(string $prefix = ''): array
    {
        $p = $prefix === '' ? '' : rtrim($prefix, '.').'.';
        $religionPath = $p === '' ? 'religion' : $p.'religion';
        $snePath = $p === '' ? 'has_special_needs' : $p.'has_special_needs';

        return [
            $p.'nationality' => ['required', 'string', 'max:100', Rule::in(config('kemis.nationalities', []))],
            $p.'county_of_birth' => ['required', 'string', 'max:100', Rule::in(config('kemis.counties', []))],
            $p.'sub_county_of_birth' => 'required|string|max:120',
            $p.'location_of_birth' => 'required|string|max:150',
            $p.'birth_certificate_entry_no' => 'required|string|max:80',
            $p.'medical_condition' => 'required|string|max:255',
            $p.'religion' => ['required', 'string', 'max:255'],
            $p.'religion_other' => 'nullable|required_if:'.$religionPath.',Other|string|max:255',
            $p.'learner_interests' => 'nullable|array',
            $p.'learner_interests.*' => 'nullable|string|max:100',
            $p.'learner_interests_other' => 'nullable|string|max:100',
            $p.'orphan_status' => ['required', Rule::in(array_keys(config('kemis.orphan_statuses', [])))],
            $p.'has_special_needs' => 'required|boolean',
            $p.'disability_type' => ['nullable', 'required_if:'.$snePath.',1', 'string', 'max:100', Rule::in(config('kemis.disability_types', []))],
        ];
    }

    public static function parentKemisValidationRules(): array
    {
        $rules = [];
        foreach (['father', 'mother', 'guardian'] as $slot) {
            $rules["{$slot}_first_name"] = 'nullable|string|max:255';
            $rules["{$slot}_middle_name"] = 'nullable|string|max:255';
            $rules["{$slot}_last_name"] = 'nullable|string|max:255';
            $rules["{$slot}_id_type"] = ['nullable', 'string', 'max:50', Rule::in(config('kemis.id_types', []))];
            $rules["{$slot}_country_of_residence"] = ['nullable', 'string', 'max:100', Rule::in(config('kemis.countries_of_residence', []))];
        }
        $rules['guardian_id_number'] = 'nullable|string|max:64';
        $rules['guardian_email'] = 'nullable|email|max:255';

        return $rules;
    }

    /**
     * Identity and contact fields that make a father/mother record complete.
     * ID document upload is intentionally excluded.
     *
     * @return list<string>
     */
    public static function parentSlotRequiredKeys(): array
    {
        return [
            'first_name',
            'last_name',
            'id_type',
            'id_number',
            'country_of_residence',
            'phone',
            'whatsapp',
            'email',
        ];
    }

    public static function validateRequest(Request $request, array $rules, string $studentScope = ''): array
    {
        $validator = Validator::make($request->all(), $rules);
        $validator->after(function (ValidatorContract $validator) use ($studentScope) {
            self::addCompletionErrors($validator, $studentScope);
        });

        return $validator->validate();
    }

    public static function addCompletionErrors(ValidatorContract $validator, string $studentScope = ''): void
    {
        $data = $validator->getData();
        foreach (self::completionMessages($data, $studentScope) as $field => $message) {
            $validator->errors()->add($field, is_array($message) ? (string) reset($message) : (string) $message);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function completionMessages(array $data, string $studentScope = ''): array
    {
        $messages = [];

        if ($studentScope === 'students') {
            foreach (($data['students'] ?? []) as $key => $student) {
                if (! is_array($student)) {
                    continue;
                }
                if (self::normalizeLearnerInterests($student['learner_interests'] ?? [], $student['learner_interests_other'] ?? null) === null) {
                    $messages["students.{$key}.learner_interests"] = 'Select at least one learner interest.';
                }
            }
        } elseif (self::normalizeLearnerInterests($data['learner_interests'] ?? [], $data['learner_interests_other'] ?? null) === null) {
            $messages['learner_interests'] = 'Select at least one learner interest.';
        }

        if (self::slotIsComplete($data, 'father') || self::slotIsComplete($data, 'mother')) {
            return $messages;
        }

        $messages['parent_required'] = 'Complete all father details or all mother details. ID document upload is optional.';

        $fatherTouched = self::slotIsTouched($data, 'father');
        $motherTouched = self::slotIsTouched($data, 'mother');
        $slotsToFlag = [];
        if ($fatherTouched && ! $motherTouched) {
            $slotsToFlag = ['father'];
        } elseif ($motherTouched && ! $fatherTouched) {
            $slotsToFlag = ['mother'];
        } elseif ($fatherTouched && $motherTouched) {
            $slotsToFlag = ['father', 'mother'];
        }

        foreach ($slotsToFlag as $slot) {
            foreach (self::missingSlotKeys($data, $slot) as $key) {
                $messages["{$slot}_{$key}"] = 'This field is required to complete '.$slot.' details.';
            }
        }

        return $messages;
    }

    public static function slotIsComplete(array $data, string $slot): bool
    {
        return self::missingSlotKeys($data, $slot) === [];
    }

    public static function slotIsTouched(array $data, string $slot): bool
    {
        foreach (self::parentSlotRequiredKeys() as $key) {
            if (self::slotValue($data, $slot, $key) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function missingSlotKeys(array $data, string $slot): array
    {
        $missing = [];
        foreach (self::parentSlotRequiredKeys() as $key) {
            if (self::slotValue($data, $slot, $key) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    private static function slotValue(array $data, string $slot, string $key): string
    {
        return trim((string) ($data["{$slot}_{$key}"] ?? ''));
    }
}

<?php

namespace App\Support;

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

        return [
            $p.'nationality' => 'nullable|string|max:100',
            $p.'county_of_birth' => 'nullable|string|max:100',
            $p.'sub_county_of_birth' => 'nullable|string|max:120',
            $p.'location_of_birth' => 'nullable|string|max:150',
            $p.'birth_certificate_entry_no' => 'nullable|string|max:80',
            $p.'medical_condition' => 'nullable|string|max:255',
            $p.'religion' => 'nullable|string|max:255',
            $p.'religion_other' => 'nullable|string|max:255',
            $p.'learner_interests' => 'nullable|array',
            $p.'learner_interests.*' => 'nullable|string|max:100',
            $p.'learner_interests_other' => 'nullable|string|max:100',
            $p.'orphan_status' => 'nullable|in:none,maternal,paternal,total',
            $p.'has_special_needs' => 'nullable|boolean',
            $p.'disability_type' => 'nullable|string|max:100',
        ];
    }

    public static function parentKemisValidationRules(): array
    {
        $rules = [];
        foreach (['father', 'mother', 'guardian'] as $slot) {
            $rules["{$slot}_first_name"] = 'nullable|string|max:255';
            $rules["{$slot}_middle_name"] = 'nullable|string|max:255';
            $rules["{$slot}_last_name"] = 'nullable|string|max:255';
            $rules["{$slot}_id_type"] = 'nullable|string|max:50';
            $rules["{$slot}_country_of_residence"] = 'nullable|string|max:100';
        }
        $rules['guardian_id_number'] = 'nullable|string|max:64';
        $rules['guardian_email'] = 'nullable|email|max:255';

        return $rules;
    }
}

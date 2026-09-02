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
            $p.'medical_condition' => 'nullable|string|max:255',
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

    /**
     * KEMIS picklists for mobile / API clients.
     *
     * @return array<string, mixed>
     */
    public static function optionsForApi(): array
    {
        return [
            'nationalities' => array_values(config('kemis.nationalities', [])),
            'counties' => array_values(config('kemis.counties', [])),
            'religions' => array_values(config('kemis.religions', [])),
            'learner_interests' => array_values(config('kemis.learner_interests', [])),
            'orphan_statuses' => config('kemis.orphan_statuses', []),
            'disability_types' => array_values(config('kemis.disability_types', [])),
            'id_types' => array_values(config('kemis.id_types', [])),
            'countries_of_residence' => array_values(config('kemis.countries_of_residence', [])),
        ];
    }

    /**
     * Map validated / request input to student model attributes for KEMIS fields.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function studentKemisAttributesFromInput(array $input): array
    {
        $attrs = [];
        foreach ([
            'nationality', 'county_of_birth', 'sub_county_of_birth', 'location_of_birth',
            'birth_certificate_entry_no', 'orphan_status', 'disability_type',
        ] as $field) {
            if (array_key_exists($field, $input)) {
                $attrs[$field] = ($input[$field] !== '' && $input[$field] !== null) ? $input[$field] : null;
            }
        }

        if (array_key_exists('medical_condition', $input)) {
            $attrs['medical_condition'] = self::defaultMedicalCondition($input['medical_condition'] ?? null);
        }

        if (array_key_exists('religion', $input) || array_key_exists('religion_other', $input)) {
            $attrs['religion'] = self::normalizeReligion($input['religion'] ?? null, $input['religion_other'] ?? null);
        }

        if (array_key_exists('learner_interests', $input) || array_key_exists('learner_interests_other', $input)) {
            $attrs['learner_interests'] = self::normalizeLearnerInterests(
                $input['learner_interests'] ?? [],
                $input['learner_interests_other'] ?? null
            );
        }

        if (array_key_exists('has_special_needs', $input)) {
            $attrs['has_special_needs'] = (bool) $input['has_special_needs'];
            if (! $attrs['has_special_needs']) {
                $attrs['disability_type'] = null;
            }
        }

        return $attrs;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function parentIdentityAttributesFromInput(array $input, string $slot): array
    {
        $attrs = \App\Models\ParentInfo::mergePersonNamesFromInput($input, $slot);
        foreach (["{$slot}_id_type", "{$slot}_id_number", "{$slot}_country_of_residence"] as $field) {
            if (array_key_exists($field, $input)) {
                $attrs[$field] = ($input[$field] !== '' && $input[$field] !== null) ? $input[$field] : null;
            }
        }

        return $attrs;
    }

    /**
     * Shared contact / residential rules used across student forms.
     *
     * @return array<string, mixed>
     */
    public static function sharedContactValidationRules(): array
    {
        return [
            'residential_area' => 'required|string|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => ['required', 'string', 'max:80', 'regex:/^[\+]?[\d\s\-\(\)]{4,25}(?:\s+[a-zA-Z\s\-\(\)\.\,]+)?$/'],
            'preferred_hospital' => 'required|string|max:255',
        ];
    }

    /**
     * Validate field-by-field so valid inputs can be saved while invalid ones are skipped.
     *
     * @param  list<int>  $allowedStudentIds
     * @return array{valid: array<string, mixed>, errors: array<string, list<string>>}
     */
    public static function partialValidateRequest(Request $request, array $rules, string $studentScope = '', array $allowedStudentIds = []): array
    {
        $data = $request->all();
        $validated = [];
        $errors = [];

        $studentWildcardRules = [];
        $topLevelRules = [];
        foreach ($rules as $key => $rule) {
            if (preg_match('/^students\.\*\.(.+)$/u', $key, $matches)) {
                $studentWildcardRules[$matches[1]] = $rule;
            } elseif ($key === 'students') {
                $topLevelRules[$key] = $rule;
            } elseif (! str_starts_with($key, 'students.')) {
                $topLevelRules[$key] = $rule;
            }
        }

        foreach ($topLevelRules as $attribute => $rule) {
            if ($attribute === 'students') {
                continue;
            }
            if (self::isUploadRule($rule) && ! $request->hasFile($attribute)) {
                continue;
            }
            $result = self::tryValidateAttribute($data, $attribute, $rule);
            if ($result['ok']) {
                if (! $request->hasFile($attribute)) {
                    $validated[$attribute] = data_get($data, $attribute);
                }
            } else {
                $errors[$attribute] = $result['errors'];
            }
        }

        $studentsIn = $data['students'] ?? null;
        if (! is_array($studentsIn) || $studentsIn === []) {
            if (isset($topLevelRules['students'])) {
                $validator = Validator::make($data, ['students' => $topLevelRules['students']]);
                if ($validator->fails()) {
                    $errors['students'] = $validator->errors()->get('students');
                }
            }
        } else {
            $validatedStudents = [];
            foreach ($studentsIn as $idx => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $studentValid = [];
                if (isset($studentWildcardRules['id'])) {
                    $idAttribute = "students.{$idx}.id";
                    $idResult = self::tryValidateAttribute($data, $idAttribute, self::indexStudentRule($studentWildcardRules['id'], $idx));
                    if (! $idResult['ok']) {
                        $errors[$idAttribute] = $idResult['errors'];
                        continue;
                    }
                    $studentId = (int) $idResult['value'];
                    if ($allowedStudentIds !== [] && ! in_array($studentId, $allowedStudentIds, true)) {
                        $errors[$idAttribute] = ['Invalid student for this link.'];
                        continue;
                    }
                    $studentValid['id'] = $studentId;
                }

                foreach ($studentWildcardRules as $field => $rule) {
                    if ($field === 'id') {
                        continue;
                    }
                    $attribute = "students.{$idx}.{$field}";
                    if (self::isUploadRule($rule) && ! $request->hasFile($attribute)) {
                        continue;
                    }
                    $result = self::tryValidateAttribute($data, $attribute, self::indexStudentRule($rule, $idx));
                    if ($result['ok']) {
                        if (! $request->hasFile($attribute)) {
                            $studentValid[$field] = data_get($data, $attribute);
                        }
                    } else {
                        $errors[$attribute] = $result['errors'];
                    }
                }

                if ($studentValid !== []) {
                    $validatedStudents[] = $studentValid;
                }
            }

            if ($validatedStudents !== []) {
                $validated['students'] = $validatedStudents;
            }
        }

        foreach (self::completionMessages($data, $studentScope) as $field => $message) {
            $errors[$field] = is_array($message) ? $message : [(string) $message];
        }

        return ['valid' => $validated, 'errors' => $errors];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, errors?: list<string>, value?: mixed}
     */
    public static function tryValidateAttribute(array $data, string $attribute, $rules): array
    {
        $validator = Validator::make($data, [$attribute => $rules]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()->get($attribute)];
        }

        return ['ok' => true, 'value' => data_get($data, $attribute)];
    }

    /**
     * @param  string|array<int, mixed>  $rule
     * @return string|array<int, mixed>
     */
    public static function indexStudentRule($rule, int|string $idx)
    {
        if (is_array($rule)) {
            return array_map(static fn ($part) => self::indexStudentRuleString((string) $part, $idx), $rule);
        }

        return self::indexStudentRuleString((string) $rule, $idx);
    }

    private static function indexStudentRuleString(string $rule, int|string $idx): string
    {
        return str_replace('students.*', "students.{$idx}", $rule);
    }

    /**
     * @param  string|array<int, mixed>  $rule
     */
    public static function isUploadRule($rule): bool
    {
        $parts = is_array($rule) ? $rule : explode('|', $rule);
        foreach ($parts as $part) {
            $part = strtolower((string) $part);
            if (str_starts_with($part, 'file') || str_starts_with($part, 'image') || str_starts_with($part, 'mimes')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parents leave this blank when the child has no condition.
     * Treat empty as "None" so the form can save.
     */
    public static function defaultMedicalCondition(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? 'None' : $value;
    }

    public static function serializeAuditValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \Carbon\Carbon) {
            return $value->toDateString();
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}

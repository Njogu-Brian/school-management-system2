<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use App\Models\CommunicationPlaceholder;

/**
 * settings (key-value) - Unified settings helper
 * This replaces both setting() and system_setting() functions
 */
if (!function_exists('setting')) {
    function setting($key, $default = null) {
        return Setting::get($key, $default);
    }
}

if (!function_exists('setting_set')) {
    function setting_set($key, $value) {
        return Setting::set($key, $value);
    }
}

/**
 * Increment a numeric setting value
 */
if (!function_exists('setting_increment')) {
    function setting_increment($key, $by = 1, $defaultStart = 0) {
        return Setting::incrementValue($key, $by, $defaultStart);
    }
}

/**
 * Get boolean setting value
 */
if (!function_exists('setting_bool')) {
    function setting_bool($key, $default = false) {
        return Setting::getBool($key, $default);
    }
}

/**
 * Set boolean setting value
 */
if (!function_exists('setting_set_bool')) {
    function setting_set_bool($key, $value) {
        return Setting::setBool($key, $value);
    }
}

/**
 * Get integer setting value
 */
if (!function_exists('setting_int')) {
    function setting_int($key, $default = 0) {
        return Setting::getInt($key, $default);
    }
}

/**
 * Set integer setting value
 */
if (!function_exists('setting_set_int')) {
    function setting_set_int($key, $value) {
        return Setting::setInt($key, $value);
    }
}

/**
 * Legacy system_setting() function - now uses settings table
 * @deprecated Use setting() instead. This is kept for backward compatibility.
 */
if (!function_exists('system_setting')) {
    function system_setting($key, $default = null) {
        return setting($key, $default);
    }
}

/**
 * Legacy system_setting_set() function - now uses settings table
 * @deprecated Use setting_set() instead. This is kept for backward compatibility.
 */
if (!function_exists('system_setting_set')) {
    function system_setting_set($key, $value) {
        return setting_set($key, $value);
    }
}

/**
 * can_access:
 *  can_access('module.feature.action') OR can_access('module','feature','action')
 */
if (!function_exists('can_access')) {
    function can_access($module, $feature = null, $action = null): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Super Admin has all access
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        // Teachers have access to their routes (they're already protected by role middleware)
        if (method_exists($user, 'hasRole') && ($user->hasRole('Teacher') || $user->hasRole('teacher'))) {
            // For teacher routes, allow access if they have the role
            // Permission checks can be done in controllers for granular control
            return true;
        }

        $permission = $feature === null
            ? (string)$module
            : "{$module}.{$feature}" . ($action ? ".{$action}" : '');

        return $user->can($permission);
    }
}

/**
 * Replace placeholders in messages (single, canonical version)
 */
if (!function_exists('replace_placeholders')) {
    function replace_placeholders(string $message, $entity = null, array $extra = []): string
    {
        $replacements = [
            '{school_name}'    => setting('school_name', 'Our School'),
            '{school_phone}'   => setting('school_phone', ''),
            '{school_email}'   => setting('school_email', ''),
            '{school_address}' => setting('school_address', ''),
            '{term}'           => setting('current_term', ''),
            '{academic_year}'  => setting('current_year', ''),
            '{date}'           => now()->format('d M Y'),
        ];

        if ($entity instanceof \App\Models\Student) {
            $replacements += [
                '{student_name}' => $entity->name ?? '',
                '{admission_no}' => $entity->admission_no ?? '',
                '{class_name}'   => optional($entity->classroom)->name,
                '{grade}'        => optional($entity->classroom)->section,
                '{parent_name}'  => optional($entity->parent)->father_name
                                    ?? optional($entity->parent)->guardian_name
                                    ?? optional($entity->parent)->mother_name,
            ];
        } elseif ($entity instanceof \App\Models\Staff) {
            $replacements += [
                '{staff_name}' => trim(($entity->first_name ?? '').' '.($entity->last_name ?? '')),
                '{role}'       => $entity->role ?? '',
                '{experience}' => $entity->experience ?? '',
            ];
        } elseif ($entity instanceof \App\Models\ParentInfo) {
            $replacements += [
                '{parent_name}' => $entity->father_name
                                    ?? $entity->guardian_name
                                    ?? $entity->mother_name
                                    ?? '',
            ];
        }

        // Optional custom placeholders (safe for CLI)
        if (class_exists(CommunicationPlaceholder::class)) {
            foreach (CommunicationPlaceholder::all() as $ph) {
                $replacements['{'.$ph->key.'}'] = (string) $ph->value;
            }
        }

        if (!empty($extra)) {
            $replacements = array_merge($replacements, $extra);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}

/**
 * Common placeholder list for help text
 */
if (!function_exists('available_placeholders')) {
    function available_placeholders(): array
    {
        return [
            '{school_name}', '{school_phone}', '{school_email}', '{school_address}', '{term}', '{academic_year}', '{date}',
            '{student_name}', '{admission_no}', '{class_name}', '{grade}', '{parent_name}',
            '{staff_name}', '{role}', '{experience}',
        ];
    }
}

if (! function_exists('format_number')) {
    function format_number($value, int $decimals = 0): string
    {
        return number_format((float)$value, $decimals);
    }
}

if (! function_exists('format_money')) {
    function format_money($value, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $currency ?: config('app.currency', 'KES');
        $locale   = $locale   ?: app()->getLocale();

        try {
            if (class_exists(\NumberFormatter::class)) {
                $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
                return $fmt->formatCurrency((float)$value, $currency);
            }
        } catch (\Throwable $e) { /* ignore */ }

        return $currency . ' ' . number_format((float)$value, 2);
    }
}

/**
 * Check if the current user is a supervisor (has subordinates)
 */
if (!function_exists('is_supervisor')) {
    function is_supervisor(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $staff = $user->staff ?? null;
        if (!$staff) return false;
        
        return $staff->subordinates()->exists();
    }
}

/**
 * Get all subordinate staff IDs for the current supervisor
 */
if (!function_exists('get_subordinate_staff_ids')) {
    function get_subordinate_staff_ids(): array
    {
        $user = Auth::user();
        if (!$user) return [];
        
        $staff = $user->staff ?? null;
        if (!$staff) return [];
        
        return $staff->subordinates()->pluck('id')->toArray();
    }
}

/**
 * Get all subordinate staff for the current supervisor
 */
if (!function_exists('get_subordinates')) {
    function get_subordinates()
    {
        $user = Auth::user();
        if (!$user) return collect();
        
        $staff = $user->staff ?? null;
        if (!$staff) return collect();
        
        return $staff->subordinates;
    }
}

/**
 * Get current academic year (as integer)
 */
if (!function_exists('get_current_academic_year')) {
    function get_current_academic_year(): ?int
    {
        $model = get_current_academic_year_model();
        return $model ? (int) $model->year : null;
    }
}

/**
 * Get current academic year model
 */
if (!function_exists('get_current_academic_year_model')) {
    function get_current_academic_year_model(): ?\App\Models\AcademicYear
    {
        // Prefer year tied to the current term; otherwise fall back to manually active, otherwise latest by year
        $term = get_current_term_model();
        if ($term && $term->academic_year_id) {
            return $term->academicYear;
        }

        $manual = \App\Models\AcademicYear::where('is_active', true)->first();
        if ($manual) {
            return $manual;
        }

        return \App\Models\AcademicYear::orderByDesc('year')->first();
    }
}

/**
 * Get current term number (1, 2, or 3)
 */
if (!function_exists('get_current_term_number')) {
    function get_current_term_number(): ?int
    {
        $term = get_current_term_model();
        if (!$term) {
            return null;
        }

        if (preg_match('/\d+/', $term->name, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }
}

/**
 * Get current term model
 */
if (!function_exists('get_current_term_model')) {
    function get_current_term_model(): ?\App\Models\Term
    {
        $tz = config('app.timezone', 'UTC');
        $today = now()->timezone($tz)->toDateString();

        // Prefer date-based determination: opening_date <= today <= closing_date
        $byDate = \App\Models\Term::whereNotNull('opening_date')
            ->whereNotNull('closing_date')
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderBy('opening_date')
            ->first();
        if ($byDate) {
            return $byDate;
        }

        // Fallback to manually flagged current term
        $manual = \App\Models\Term::where('is_current', true)->first();
        if ($manual) {
            return $manual;
        }

        // Fallback: nearest upcoming term, otherwise latest past term
        $upcoming = \App\Models\Term::whereNotNull('opening_date')
            ->whereDate('opening_date', '>', $today)
            ->orderBy('opening_date')
            ->first();
        if ($upcoming) {
            return $upcoming;
        }

        return \App\Models\Term::whereNotNull('closing_date')
            ->orderByDesc('closing_date')
            ->first();
    }
}

/**
 * Get current term ID
 */
if (!function_exists('get_current_term_id')) {
    function get_current_term_id(): ?int
    {
        $term = get_current_term_model();
        return $term ? $term->id : null;
    }
}

/**
 * Get current academic year ID
 */
if (!function_exists('get_current_academic_year_id')) {
    function get_current_academic_year_id(): ?int
    {
        $year = get_current_academic_year_model();
        return $year ? $year->id : null;
    }
}

/**
 * Check if a staff member is supervised by the current user
 */
if (!function_exists('is_my_subordinate')) {
    function is_my_subordinate($staffId): bool
    {
        $subordinateIds = get_subordinate_staff_ids();
        return in_array($staffId, $subordinateIds);
    }
}

/**
 * Get classroom IDs for classes assigned to subordinates
 */
/**
 * Extract local phone number from stored full international format.
 * Removes the country code prefix to show only the local number.
 */
if (!function_exists('extract_local_phone')) {
    function extract_local_phone(?string $fullPhone, ?string $countryCode = '+254'): ?string
    {
        if (!$fullPhone) {
            return null;
        }
        
        // Normalize country code (handle +ke or ke to +254)
        if (strtolower($countryCode) === '+ke' || strtolower($countryCode) === 'ke') {
            $countryCode = '+254';
        }
        $cleanCode = ltrim($countryCode, '+');
        
        // Remove all non-digits from full phone
        $digits = preg_replace('/\D+/', '', $fullPhone);
        
        // If phone starts with country code, remove it
        if (str_starts_with($digits, $cleanCode)) {
            return substr($digits, strlen($cleanCode));
        }
        
        // If it's already just local digits, return as is
        return $digits;
    }
}

if (!function_exists('get_subordinate_classroom_ids')) {
    function get_subordinate_classroom_ids(): array
    {
        $subordinateIds = get_subordinate_staff_ids();
        if (empty($subordinateIds)) return [];
        
        return \DB::table('classroom_subjects')
            ->whereIn('staff_id', $subordinateIds)
            ->distinct()
            ->pluck('classroom_id')
            ->toArray();
    }
}
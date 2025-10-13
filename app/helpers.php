<?php

use App\Models\Setting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\CommunicationPlaceholder; // <-- create model below
/**
 * Fetch value from key-value settings table (settings)
 */
if (!function_exists('setting')) {
    function setting($key, $default = null) {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}

if (!function_exists('setting_set')) {
    function setting_set($key, $value) {
        return Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}

/**
 * Fetch value from system_settings table (single row, column-based)
 */
if (!function_exists('system_setting')) {
    function system_setting($key, $default = null) {
        $system = SystemSetting::first();
        return $system ? ($system->$key ?? $default) : $default;
    }
}

if (!function_exists('system_setting_set')) {
    function system_setting_set($key, $value) {
        $system = SystemSetting::first();
        if (!$system) {
            return SystemSetting::create([$key => $value]);
        }
        $system->$key = $value;
        $system->save();
        return $system;
    }
}

/**
 * Check if the current user can access a given module/feature/action
 */
if (!function_exists('can_access')) {
    function can_access($module, $feature, $action)
    {
        $user = Auth::user();
        if (!$user) return false;

        // Super Admins always have full access
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $permission = "{$module}.{$feature}.{$action}";
        return $user->can($permission);
    }
}

/**
 * Replace placeholders in SMS/Email messages
 */
if (!function_exists('replace_placeholders')) {
    function replace_placeholders($message, $entity = null)
    {
        $replacements = [
            '{school_name}' => setting('school_name', system_setting('school_name', 'Our School')),
            '{date}' => now()->format('d M Y'),
        ];

        if ($entity instanceof \App\Models\Student) {
            $replacements += [
                '{student_name}' => $entity->name ?? '',
                '{admission_no}' => $entity->admission_no ?? '',
                '{class_name}' => optional($entity->classroom)->name,
                '{grade}' => optional($entity->classroom)->section,
                '{parent_name}' => optional($entity->parent)->father_name ?? optional($entity->parent)->guardian_name,
            ];
        }

        if ($entity instanceof \App\Models\Staff) {
            $replacements += [
                '{staff_name}' => trim($entity->first_name . ' ' . $entity->last_name),
                '{role}' => $entity->role ?? '',
            ];
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}


if (!function_exists('replace_placeholders')) {
    /**
     * Replace placeholders in SMS/Email messages.
     * $entity may be Student, Staff, ParentInfo, etc.
     * $extra lets you inject ad-hoc values: ['{otp}' => '123456']
     */
    function replace_placeholders(string $message, $entity = null, array $extra = []): string
    {
        // Global (brand/system) placeholders
        $replacements = [
            '{school_name}'    => setting('school_name', system_setting('school_name', 'Our School')),
            '{school_phone}'   => system_setting('phone', ''),
            '{school_email}'   => system_setting('email', ''),
            '{school_address}' => system_setting('address', ''),
            '{term}'           => system_setting('current_term', ''),
            '{academic_year}'  => system_setting('current_year', ''),
            '{date}'           => now()->format('d M Y'),
        ];

        // Entity-based placeholders
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

        // Custom placeholders from DB (optional UI later)
        if (class_exists(CommunicationPlaceholder::class)) {
            foreach (CommunicationPlaceholder::all() as $ph) {
                $replacements['{'.$ph->key.'}'] = (string) $ph->value;
            }
        }

        // Extra ad-hoc values win
        if (!empty($extra)) {
            $replacements = array_merge($replacements, $extra);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}

/**
 * Convenience: list common placeholders for help text in blades.
 */
if (!function_exists('available_placeholders')) {
    function available_placeholders(): array
    {
        return [
            // Global
            '{school_name}', '{school_phone}', '{school_email}', '{school_address}', '{term}', '{academic_year}', '{date}',
            // Student/Parent
            '{student_name}', '{admission_no}', '{class_name}', '{grade}', '{parent_name}',
            // Staff
            '{staff_name}', '{role}', '{experience}',
        ];
    }
}

/**
 * Map-based replacer if you ever want to skip entity logic and just pass a map.
 */
if (!function_exists('replace_placeholders_with_map')) {
    function replace_placeholders_with_map(string $message, array $map): string
    {
        return str_replace(array_keys($map), array_values($map), $message);
    }
}

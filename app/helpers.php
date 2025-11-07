<?php

use App\Models\Setting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\CommunicationPlaceholder;

/**
 * settings (key-value)
 */
if (!function_exists('setting')) {
    function setting($key, $default = null) {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}

if (!function_exists('setting_set')) {
    function setting_set($key, $value) {
        return Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

/**
 * system_settings (single-row)
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
        if (!$system) return SystemSetting::create([$key => $value]);
        $system->$key = $value;
        $system->save();
        return $system;
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
            '{school_name}'    => setting('school_name', system_setting('school_name', 'Our School')),
            '{school_phone}'   => system_setting('phone', ''),
            '{school_email}'   => system_setting('email', ''),
            '{school_address}' => system_setting('address', ''),
            '{term}'           => system_setting('current_term', ''),
            '{academic_year}'  => system_setting('current_year', ''),
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

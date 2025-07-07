<?php

use App\Models\Setting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

// ================= Permission Helper ===================

    if (!function_exists('can_access')) {
        function can_access($module, $feature, $action)
        {
            $user = Auth::user();
            if (!$user) return false;

            foreach ($user->roles as $role) {
                $permission = $role->permissions()
                    ->where('module', $module)
                    ->where('feature', $feature)
                    ->first();

                if ($permission && $permission->pivot && $permission->pivot->{'can_' . $action}) {
                    return true;
                }
            }

            return false;
        }
    }

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

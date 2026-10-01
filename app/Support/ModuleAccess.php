<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ModuleAccess
{
    private const CACHE_KEY = 'module_access.enabled_v1';

    private const CONFIGURED_KEY = 'modules_configured';

    private const ENABLED_KEY = 'enabled_modules';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function moduleCatalog(): array
    {
        return config('modules.modules', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function submoduleCatalog(): array
    {
        return config('modules.submodules', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function featureCatalog(): array
    {
        return config('modules.features', []);
    }

    /**
     * @return list<string>
     */
    public static function alwaysOn(): array
    {
        return array_values(config('modules.always_on', ['settings', 'profile']));
    }

    public static function isAlwaysOn(string $key): bool
    {
        return in_array($key, self::alwaysOn(), true);
    }

    /**
     * Whether module preferences have been saved with the new catalog.
     */
    public static function isConfigured(): bool
    {
        return Setting::getBool(self::CONFIGURED_KEY, false);
    }

    /**
     * All module + submodule keys that should appear enabled in the UI / runtime.
     *
     * @return list<string>
     */
    public static function enabledKeys(): array
    {
        return Cache::remember(self::CACHE_KEY, 60, function () {
            $moduleKeys = array_keys(self::moduleCatalog());
            $submoduleKeys = array_keys(self::submoduleCatalog());
            $allKeys = array_values(array_unique(array_merge($moduleKeys, $submoduleKeys)));

            if (! self::isConfigured()) {
                return $allKeys;
            }

            $stored = self::readStoredEnabled();
            $enabled = array_values(array_intersect($stored, $allKeys));

            foreach (self::alwaysOn() as $key) {
                if (isset(self::moduleCatalog()[$key]) || isset(self::submoduleCatalog()[$key])) {
                    if (! in_array($key, $enabled, true)) {
                        $enabled[] = $key;
                    }
                }
            }

            // Forward-compat: brand-new catalog keys added after last save default ON.
            $knownAtLastSave = Setting::getJson('modules_catalog_keys', null);
            if (is_array($knownAtLastSave) && $knownAtLastSave !== []) {
                foreach ($allKeys as $key) {
                    if (! in_array($key, $knownAtLastSave, true) && ! in_array($key, $enabled, true)) {
                        $enabled[] = $key;
                    }
                }
            }

            return array_values(array_unique($enabled));
        });
    }

    public static function isModuleEnabled(string $key): bool
    {
        if (self::isAlwaysOn($key)) {
            return true;
        }

        // Profile / dashboards are not module-gated.
        if ($key === 'profile' || $key === 'dashboard') {
            return true;
        }

        if (! isset(self::moduleCatalog()[$key])) {
            // Unknown section keys stay visible (role gate only).
            return true;
        }

        return in_array($key, self::enabledKeys(), true);
    }

    public static function isSubmoduleEnabled(string $key): bool
    {
        $catalog = self::submoduleCatalog();
        if (! isset($catalog[$key])) {
            return true;
        }

        $parent = (string) ($catalog[$key]['parent'] ?? '');
        if ($parent !== '' && ! self::isModuleEnabled($parent)) {
            return false;
        }

        if (! self::isConfigured()) {
            return true;
        }

        return in_array($key, self::enabledKeys(), true);
    }

    public static function isFeatureEnabled(string $key, ?bool $default = null): bool
    {
        $catalog = self::featureCatalog();
        $fallback = $default;
        if ($fallback === null) {
            $metaDefault = $catalog[$key]['default'] ?? false;
            $fallback = is_bool($metaDefault) ? $metaDefault : (bool) $metaDefault;
        }

        if (! array_key_exists($key, $catalog)) {
            return Setting::getBool($key, $fallback);
        }

        $type = $catalog[$key]['type'] ?? 'bool';
        if ($type !== 'bool') {
            return true;
        }

        // If never set, use catalog default.
        $raw = Setting::get($key, null);
        if ($raw === null) {
            return $fallback;
        }

        return Setting::getBool($key, $fallback);
    }

    /**
     * Persist enabled module/submodule keys and mark preferences as configured.
     *
     * @param  list<string>  $keys
     */
    public static function saveEnabled(array $keys): void
    {
        $moduleKeys = array_keys(self::moduleCatalog());
        $submoduleKeys = array_keys(self::submoduleCatalog());
        $allowed = array_flip(array_merge($moduleKeys, $submoduleKeys));

        $clean = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if (isset($allowed[$key])) {
                $clean[] = $key;
            }
        }

        foreach (self::alwaysOn() as $key) {
            if (isset($allowed[$key]) && ! in_array($key, $clean, true)) {
                $clean[] = $key;
            }
        }

        // Disable submodules whose parent is off.
        $clean = array_values(array_filter($clean, function (string $key) use ($clean) {
            $meta = self::submoduleCatalog()[$key] ?? null;
            if (! $meta) {
                return true;
            }
            $parent = (string) ($meta['parent'] ?? '');

            return $parent === '' || in_array($parent, $clean, true);
        }));

        Setting::set(self::ENABLED_KEY, json_encode(array_values(array_unique($clean))));
        Setting::setBool(self::CONFIGURED_KEY, true);
        Setting::setJson('modules_catalog_keys', array_merge($moduleKeys, $submoduleKeys));
        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    private static function readStoredEnabled(): array
    {
        $raw = Setting::get(self::ENABLED_KEY);
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($decoded)) {
            return [];
        }

        $legacyMap = config('modules.legacy_map', []);
        $out = [];
        foreach ($decoded as $key) {
            $key = (string) $key;
            if (isset($legacyMap[$key])) {
                $out[] = $legacyMap[$key];
            } else {
                $out[] = $key;
            }
        }

        return array_values(array_unique($out));
    }
}

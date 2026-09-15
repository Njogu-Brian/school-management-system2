<?php

namespace App\Support;

/**
 * Worldwide telephone country calling codes.
 * Kenya (+254) is always first; every other country follows alphabetically by name.
 */
class CountryDialCodes
{
    /**
     * @return list<array{code: string, label: string, name: string}>
     */
    public static function options(): array
    {
        $path = resource_path('data/country_codes.php');
        if (! is_file($path)) {
            return [['code' => '+254', 'label' => 'Kenya (+254)', 'name' => 'Kenya']];
        }

        /** @var list<array{code?: string, name?: string, label?: string}>|array<string, string> $raw */
        $raw = include $path;
        if (! is_array($raw)) {
            return [['code' => '+254', 'label' => 'Kenya (+254)', 'name' => 'Kenya']];
        }

        $rows = [];
        foreach ($raw as $key => $value) {
            if (is_array($value) && isset($value['code'], $value['name'])) {
                $code = (string) $value['code'];
                $name = (string) $value['name'];
            } elseif (is_string($key) && is_string($value)) {
                $code = $key;
                $name = preg_replace('/\s*\([^)]*\)\s*$/', '', $value) ?: $value;
            } else {
                continue;
            }
            if ($code === '' || $name === '') {
                continue;
            }
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'label' => $name.' ('.$code.')',
            ];
        }

        $kenya = [];
        $others = [];
        foreach ($rows as $row) {
            if (strcasecmp($row['name'], 'Kenya') === 0) {
                $kenya[] = $row;
            } else {
                $others[] = $row;
            }
        }
        usort($others, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return array_values(array_merge($kenya, $others));
    }

    /**
     * Ordered map of dial code => label. Duplicate codes keep the last label.
     *
     * @return array<string, string>
     */
    public static function map(): array
    {
        $map = [];
        foreach (self::options() as $row) {
            $map[$row['code']] = $row['label'];
        }

        return $map;
    }
}

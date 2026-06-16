<?php

namespace App\Services\Finance;

use App\Models\Account;
use Illuminate\Support\Str;

class AccountCodeService
{
    /** @var array<string, array{int, int}> */
    protected static array $typeRanges = [
        Account::TYPE_ASSET => [1000, 1999],
        Account::TYPE_LIABILITY => [2000, 2999],
        Account::TYPE_EQUITY => [3000, 3999],
        Account::TYPE_REVENUE => [4000, 4999],
        Account::TYPE_EXPENSE => [5000, 5999],
    ];

    public static function suggest(?Account $parent, string $accountType): string
    {
        if ($parent) {
            $siblings = Account::query()
                ->where('parent_id', $parent->id)
                ->orderByDesc('code')
                ->pluck('code');

            $base = (int) $parent->code;
            $next = $base + 10;

            foreach ($siblings as $code) {
                $numeric = (int) preg_replace('/\D/', '', (string) $code);
                if ($numeric >= $next) {
                    $next = $numeric + 1;
                }
            }

            return (string) $next;
        }

        [$min, $max] = self::$typeRanges[$accountType] ?? [9000, 9999];

        $highest = Account::query()
            ->where('account_type', $accountType)
            ->whereNull('parent_id')
            ->orderByDesc('code')
            ->value('code');

        if ($highest) {
            $numeric = (int) preg_replace('/\D/', '', (string) $highest);
            $candidate = $numeric + 100;

            return (string) min(max($candidate, $min), $max);
        }

        return (string) $min;
    }

    public static function slugFromName(string $name): string
    {
        return strtoupper(Str::slug($name, '-'));
    }
}

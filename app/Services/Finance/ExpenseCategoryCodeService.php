<?php

namespace App\Services\Finance;

use App\Models\ExpenseCategory;
use Illuminate\Support\Str;

class ExpenseCategoryCodeService
{
    public static function suggest(?ExpenseCategory $parent, string $name): string
    {
        $slug = strtoupper(Str::slug($name, '-'));
        $slug = preg_replace('/[^A-Z0-9-]/', '', $slug) ?: 'CAT';

        if ($parent) {
            $base = $parent->code;
            $candidate = $base . '-' . $slug;
            $suffix = 1;

            while (ExpenseCategory::where('code', $candidate)->exists()) {
                $candidate = $base . '-' . $slug . '-' . $suffix;
                $suffix++;
            }

            return $candidate;
        }

        $candidate = $slug;
        $suffix = 1;

        while (ExpenseCategory::where('code', $candidate)->exists()) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}

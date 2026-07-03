<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * Smart student name / admission search (multi-token + phonetic fallback).
 */
class StudentSearchService
{
    public function applySearch(Builder $query, string $raw): void
    {
        $raw = trim($raw);
        if ($raw === '') {
            return;
        }

        $tokens = preg_split('/\s+/', $raw) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));

        if ($tokens === []) {
            return;
        }

        $query->where(function ($outer) use ($tokens, $raw) {
            foreach ($tokens as $token) {
                $outer->where(function ($q) use ($token) {
                    $this->applyTokenMatch($q, $token);
                });
            }

            // Full-string admission match (tolerant of dashes/spaces).
            $normalizedAdmission = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $raw), 'UTF-8');
            if ($normalizedAdmission !== '') {
                $outer->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(admission_number, " ", ""), "-", ""), "/", "")) LIKE ?',
                    ['%'.$normalizedAdmission.'%']
                );
            }

            $parentLike = '%'.addcslashes(mb_strtolower($raw, 'UTF-8'), '%_\\').'%';
            $outer->orWhereHas('parent', function ($p) use ($parentLike) {
                $p->whereRaw('LOWER(father_name) LIKE ?', [$parentLike])
                    ->orWhereRaw('LOWER(mother_name) LIKE ?', [$parentLike])
                    ->orWhereRaw('LOWER(guardian_name) LIKE ?', [$parentLike])
                    ->orWhere('father_phone', 'like', $parentLike)
                    ->orWhere('mother_phone', 'like', $parentLike)
                    ->orWhere('guardian_phone', 'like', $parentLike);
            });
        });
    }

    private function applyTokenMatch(Builder $q, string $token): void
    {
        $like = '%'.addcslashes(mb_strtolower($token, 'UTF-8'), '%_\\').'%';

        $q->where(function ($inner) use ($like, $token) {
            $inner->whereRaw('LOWER(first_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(middle_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(admission_number) LIKE ?', [$like]);

            if (mb_strlen($token) >= 3) {
                $inner->orWhereRaw('SOUNDEX(first_name) = SOUNDEX(?)', [$token])
                    ->orWhereRaw('SOUNDEX(middle_name) = SOUNDEX(?)', [$token])
                    ->orWhereRaw('SOUNDEX(last_name) = SOUNDEX(?)', [$token]);
            }
        });
    }
}

<?php

namespace App\Services\Admissions;

use App\Models\Academics\Classroom;

/**
 * Interview rules by programme:
 * - Foundation: not applicable
 * - PP1: optional
 * - PP2 and Grade 1–9: required
 */
class AdmissionInterviewPolicy
{
    public const REQUIRED = 'required';

    public const OPTIONAL = 'optional';

    public const NOT_APPLICABLE = 'not_applicable';

    public function forClassroom(?Classroom $classroom): string
    {
        if (! $classroom) {
            return self::OPTIONAL;
        }

        return $this->forClassName((string) $classroom->name);
    }

    public function forClassName(string $name): string
    {
        $normalized = strtolower(trim($name));

        if (str_contains($normalized, 'foundation') || str_contains($normalized, 'creche') || str_contains($normalized, 'baby')) {
            return self::NOT_APPLICABLE;
        }

        if (preg_match('/\bpp\s*1\b|^pp1/', $normalized)) {
            return self::OPTIONAL;
        }

        if (preg_match('/\bpp\s*2\b|^pp2/', $normalized)) {
            return self::REQUIRED;
        }

        if (preg_match('/\bgrade\s*([1-9])\b/', $normalized)) {
            return self::REQUIRED;
        }

        return self::OPTIONAL;
    }

    public function mustInterviewBeforeEnroll(string $policy, ?string $outcome = null): bool
    {
        if ($policy === self::NOT_APPLICABLE) {
            return false;
        }

        if ($policy === self::OPTIONAL) {
            return false;
        }

        // required
        return ! in_array($outcome, ['passed', 'skipped'], true);
    }

    public function label(string $policy): string
    {
        return match ($policy) {
            self::REQUIRED => 'Compulsory',
            self::OPTIONAL => 'Optional',
            self::NOT_APPLICABLE => 'Not applicable',
            default => ucfirst(str_replace('_', ' ', $policy)),
        };
    }
}

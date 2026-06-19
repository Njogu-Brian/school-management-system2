<?php

namespace App\Rules;

use App\Support\AcademicContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TermBelongsToAcademicYear implements ValidationRule
{
    public function __construct(
        private readonly ?int $academicYearId,
        private readonly string $yearField = 'academic_year_id',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $yearId = $this->academicYearId;
        if (! $yearId || ! $value) {
            return;
        }

        if (! AcademicContext::termBelongsToYear((int) $value, (int) $yearId)) {
            $fail('The selected term does not belong to the selected academic year.');
        }
    }
}

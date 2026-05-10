<?php

namespace App\Services;

use App\Models\Family;
use App\Models\ParentInfo;
use App\Models\Student;

class FamilyLinkingService
{
    /**
     * Try to find an existing ParentInfo record that matches any provided phone/email.
     *
     * @param  array<string, string|null>  $contacts
     */
    public function findMatchingParent(array $contacts): ?ParentInfo
    {
        $phones = array_values(array_unique(array_filter([
            $contacts['father_phone'] ?? null,
            $contacts['mother_phone'] ?? null,
            $contacts['guardian_phone'] ?? null,
        ])));

        $emails = array_values(array_unique(array_filter(array_map(
            fn ($e) => $e ? strtolower(trim((string) $e)) : null,
            [
                $contacts['father_email'] ?? null,
                $contacts['mother_email'] ?? null,
                $contacts['guardian_email'] ?? null,
            ]
        ))));

        if (empty($phones) && empty($emails)) {
            return null;
        }

        return ParentInfo::query()
            ->when(!empty($phones), function ($q) use ($phones) {
                $q->where(function ($qq) use ($phones) {
                    $qq->whereIn('father_phone', $phones)
                        ->orWhereIn('mother_phone', $phones)
                        ->orWhereIn('guardian_phone', $phones);
                });
            })
            ->when(!empty($emails), function ($q) use ($emails) {
                $q->orWhere(function ($qq) use ($emails) {
                    $qq->whereIn('father_email', $emails)
                        ->orWhereIn('mother_email', $emails)
                        ->orWhereIn('guardian_email', $emails);
                });
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * Ensure the given student is in the same family as the student's parent (or create one).
     */
    public function ensureFamilyForStudentFromParent(Student $student, ParentInfo $parent): ?int
    {
        // If student already has a family, keep it.
        if ($student->family_id) {
            return (int) $student->family_id;
        }

        // Find any existing student under this parent (canonical sibling).
        $existing = Student::withArchived()
            ->where('parent_id', $parent->id)
            ->orderBy('id')
            ->first();

        if ($existing && $existing->family_id) {
            $student->update(['family_id' => $existing->family_id]);
            return (int) $existing->family_id;
        }

        // No family exists yet; create one and assign to both.
        $family = Family::create([
            'guardian_name' => $parent->guardian_name ?? $parent->father_name ?? $parent->mother_name ?? 'Family',
            'phone' => $parent->guardian_phone ?? $parent->father_phone ?? $parent->mother_phone,
            'email' => $parent->guardian_email ?? $parent->father_email ?? $parent->mother_email,
            'father_name' => $parent->father_name,
            'mother_name' => $parent->mother_name,
            'father_phone' => $parent->father_phone,
            'mother_phone' => $parent->mother_phone,
            'father_email' => $parent->father_email,
            'mother_email' => $parent->mother_email,
        ]);

        if ($existing) {
            $existing->update(['family_id' => $family->id]);
        }
        $student->update(['family_id' => $family->id]);

        return (int) $family->id;
    }
}


<?php

namespace App\Services;

use App\Models\Family;
use App\Models\ParentInfo;
use App\Models\Student;

class FamilyLinkingService
{
    /**
     * Find an existing ParentInfo to reuse when creating/enrolling a student.
     *
     * Rules:
     * - Match on father or mother phone/email only (guardian is informational).
     * - Same mother + same/empty father → reuse (true siblings).
     * - Same mother + different father → do NOT reuse (split household).
     * - Same father + different mother → do NOT reuse.
     *
     * @param  array<string, string|null>  $contacts
     */
    public function findMatchingParent(array $contacts): ?ParentInfo
    {
        $incomingFatherPhones = $this->normalizedPhoneSet([
            $contacts['father_phone'] ?? null,
            $contacts['father_whatsapp'] ?? null,
        ]);
        $incomingMotherPhones = $this->normalizedPhoneSet([
            $contacts['mother_phone'] ?? null,
            $contacts['mother_whatsapp'] ?? null,
        ]);
        $incomingFatherEmail = $this->normalizedEmail($contacts['father_email'] ?? null);
        $incomingMotherEmail = $this->normalizedEmail($contacts['mother_email'] ?? null);

        if (
            $incomingFatherPhones === []
            && $incomingMotherPhones === []
            && $incomingFatherEmail === null
            && $incomingMotherEmail === null
        ) {
            return null;
        }

        $candidates = ParentInfo::query()
            ->where(function ($q) use ($incomingFatherPhones, $incomingMotherPhones, $incomingFatherEmail, $incomingMotherEmail) {
                foreach ($incomingFatherPhones as $needle) {
                    $q->orWhere('father_phone', 'like', '%'.$needle.'%')
                        ->orWhere('father_whatsapp', 'like', '%'.$needle.'%');
                }
                foreach ($incomingMotherPhones as $needle) {
                    $q->orWhere('mother_phone', 'like', '%'.$needle.'%')
                        ->orWhere('mother_whatsapp', 'like', '%'.$needle.'%');
                }
                if ($incomingFatherEmail) {
                    $q->orWhereRaw('LOWER(TRIM(father_email)) = ?', [$incomingFatherEmail]);
                }
                if ($incomingMotherEmail) {
                    $q->orWhereRaw('LOWER(TRIM(mother_email)) = ?', [$incomingMotherEmail]);
                }
            })
            ->orderBy('id')
            ->get();

        foreach ($candidates as $parent) {
            $fatherMatch = $this->slotMatches(
                $parent,
                'father',
                $incomingFatherPhones,
                $incomingFatherEmail
            );
            $motherMatch = $this->slotMatches(
                $parent,
                'mother',
                $incomingMotherPhones,
                $incomingMotherEmail
            );

            if (! $fatherMatch && ! $motherMatch) {
                continue;
            }

            // Mother matches but incoming father conflicts with existing father → separate household.
            if (
                $motherMatch
                && ! $fatherMatch
                && $this->slotConflicts($parent, 'father', $incomingFatherPhones, $incomingFatherEmail)
            ) {
                continue;
            }

            // Father matches but incoming mother conflicts with existing mother → separate household.
            if (
                $fatherMatch
                && ! $motherMatch
                && $this->slotConflicts($parent, 'mother', $incomingMotherPhones, $incomingMotherEmail)
            ) {
                continue;
            }

            return $parent;
        }

        return null;
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
        // Family identity prefers mother, then father. Guardian is stored as info only on parent_info.
        $family = Family::create([
            'guardian_name' => $parent->mother_name ?? $parent->father_name ?? $parent->guardian_name ?? 'Family',
            'phone' => $parent->mother_phone ?? $parent->father_phone ?? $parent->guardian_phone,
            'email' => $parent->mother_email ?? $parent->father_email ?? $parent->guardian_email,
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

    /**
     * @param  list<string|null>  $values
     * @return list<string>
     */
    private function normalizedPhoneSet(array $values): array
    {
        $out = [];
        foreach ($values as $value) {
            $n = normalize_contact_for_parent_match($value);
            if ($n !== '') {
                $out[$n] = $n;
            }
        }

        return array_values($out);
    }

    private function normalizedEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * @param  list<string>  $incomingPhones
     */
    private function slotMatches(ParentInfo $parent, string $slot, array $incomingPhones, ?string $incomingEmail): bool
    {
        $phoneCols = $slot === 'father'
            ? ['father_phone', 'father_whatsapp']
            : ['mother_phone', 'mother_whatsapp'];
        $emailCol = $slot === 'father' ? 'father_email' : 'mother_email';

        if ($incomingEmail) {
            $existingEmail = $this->normalizedEmail($parent->{$emailCol} ?? null);
            if ($existingEmail && $existingEmail === $incomingEmail) {
                return true;
            }
        }

        foreach ($phoneCols as $col) {
            $existing = normalize_contact_for_parent_match((string) ($parent->{$col} ?? ''));
            if ($existing !== '' && in_array($existing, $incomingPhones, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when both sides have a contact for this slot and they do not match.
     *
     * @param  list<string>  $incomingPhones
     */
    private function slotConflicts(ParentInfo $parent, string $slot, array $incomingPhones, ?string $incomingEmail): bool
    {
        $incomingHas = $incomingPhones !== [] || $incomingEmail !== null;
        if (! $incomingHas) {
            return false;
        }

        $phoneCols = $slot === 'father'
            ? ['father_phone', 'father_whatsapp']
            : ['mother_phone', 'mother_whatsapp'];
        $emailCol = $slot === 'father' ? 'father_email' : 'mother_email';

        $existingPhones = $this->normalizedPhoneSet([
            $parent->{$phoneCols[0]} ?? null,
            $parent->{$phoneCols[1]} ?? null,
        ]);
        $existingEmail = $this->normalizedEmail($parent->{$emailCol} ?? null);
        $existingHas = $existingPhones !== [] || $existingEmail !== null;

        if (! $existingHas) {
            return false;
        }

        return ! $this->slotMatches($parent, $slot, $incomingPhones, $incomingEmail);
    }
}

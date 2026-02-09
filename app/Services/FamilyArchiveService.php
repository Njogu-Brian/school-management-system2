<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Handles family lifecycle when students are archived/alumni or restored.
 * - When a child is archived or becomes alumni: delete the family if only one
 *   active child remains or if all children are archived/alumni; store family
 *   id on students so we can restore the family when children are restored.
 * - When a child (or children) are restored: restore the family by creating
 *   a new family and linking all students that shared the same archived_family_id.
 */
class FamilyArchiveService
{
    /**
     * Call after a student has been archived or marked as alumni.
     * If the family has 0 or 1 active (non-archived, non-alumni) members, remove
     * the family: store family id on each member as archived_family_id, unlink all, delete family.
     */
    public function onStudentArchivedOrAlumni(Student $student): void
    {
        $familyId = $student->family_id;
        if (!$familyId) {
            return;
        }

        $family = Family::find($familyId);
        if (!$family) {
            return;
        }

        // Count active members: not archived and not alumni
        $activeCount = Student::where('family_id', $familyId)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->count();

        if ($activeCount > 1) {
            return;
        }

        DB::transaction(function () use ($family, $familyId) {
            $members = Student::where('family_id', $familyId)->get();
            foreach ($members as $s) {
                $s->archived_family_id = $familyId;
                $s->family_id = null;
                $s->save();
            }
            $family->delete();
        });
    }

    /**
     * Call after a student has been restored (single or bulk).
     * If the student has archived_family_id set, find all students with that id;
     * if 2+ exist, create a new family and link them all and clear archived_family_id.
     */
    public function onStudentRestored(Student $student): void
    {
        $archivedFamilyId = $student->archived_family_id;
        if (!$archivedFamilyId) {
            return;
        }

        $siblings = Student::where('archived_family_id', $archivedFamilyId)->get();
        if ($siblings->count() < 2) {
            foreach ($siblings as $s) {
                $s->archived_family_id = null;
                $s->save();
            }
            return;
        }

        DB::transaction(function () use ($siblings) {
            $first = $siblings->first();
            $p = $first->parent;
            $guardianName = 'Restored family';
            $phone = null;
            $email = null;
            if ($p) {
                $guardianName = $p->guardian_name ?? $p->father_name ?? $p->mother_name ?? $guardianName;
                $phone = $p->guardian_phone ?? $p->father_phone ?? $p->mother_phone;
                $email = $p->guardian_email ?? $p->father_email ?? $p->mother_email;
            }

            $family = Family::create([
                'guardian_name' => $guardianName,
                'phone'         => $phone,
                'email'         => $email,
            ]);

            foreach ($siblings as $s) {
                $s->family_id = $family->id;
                $s->archived_family_id = null;
                $s->save();
            }
        });
    }
}

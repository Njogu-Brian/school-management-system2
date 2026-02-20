<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyUpdateLink;
use App\Models\Student;
use Illuminate\Console\Command;

class ResetProfileUpdateLinks extends Command
{
    protected $signature = 'families:reset-profile-update-links
                            {--dry-run : List what would be done without making changes}';

    protected $description = 'Regenerate profile update links for families and create links for students without families (no new families created).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $linksCreated = 0;
        $linksReset = 0;
        $studentLinksCreated = 0;

        // 1. Families: reset or create links (no new families)
        $families = Family::with('updateLink')->get();
        foreach ($families as $family) {
            if ($family->updateLink) {
                if (!$dryRun) {
                    $family->updateLink->update([
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                        'last_sent_at' => null,
                    ]);
                }
                $linksReset++;
            } else {
                if (!$dryRun) {
                    FamilyUpdateLink::create([
                        'family_id' => $family->id,
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                    ]);
                }
                $linksCreated++;
            }
        }

        // 2. Students without families: create student-only links (no family created)
        $studentsWithoutFamilies = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->whereNull('family_id')
            ->get();

        foreach ($studentsWithoutFamilies as $student) {
            $existingLink = FamilyUpdateLink::where('student_id', $student->id)->whereNull('family_id')->first();
            if ($existingLink) {
                if (!$dryRun) {
                    $existingLink->update([
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                        'last_sent_at' => null,
                    ]);
                }
                $linksReset++;
            } else {
                if (!$dryRun) {
                    FamilyUpdateLink::create([
                        'family_id' => null,
                        'student_id' => $student->id,
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                    ]);
                }
                $studentLinksCreated++;
            }
        }

        $this->info('Profile update links regenerated.');
        $this->info('  - Family links reset: ' . $linksReset . ', created: ' . $linksCreated);
        $this->info('  - Student-only links (no family) created: ' . $studentLinksCreated);

        return self::SUCCESS;
    }
}

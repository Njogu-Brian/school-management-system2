<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\FamilyUpdateLink;
use App\Models\Student;
use Illuminate\Console\Command;

class EnsureProfileUpdateLinks extends Command
{
    protected $signature = 'families:ensure-profile-update-links
                            {--dry-run : List what would be done without making changes}';
    protected $description = 'Ensure every active student has a profile update link. Students without a family get one created; siblings share their family\'s link.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $familiesCreated = 0;
        $linksCreated = 0;

        // Step 1: Create families for active students without one (each gets their own family → individual link)
        $studentsWithoutFamilies = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->whereNull('family_id')
            ->with('parent')
            ->get();

        foreach ($studentsWithoutFamilies as $student) {
            if ($dryRun) {
                $this->line("Would create family for student #{$student->id} ({$student->admission_number})");
                $familiesCreated++;
                continue;
            }
            $family = Family::create([
                'guardian_name' => $student->parent
                    ? ($student->parent->guardian_name ?? $student->parent->father_name ?? $student->parent->mother_name ?? 'Family ' . $student->admission_number)
                    : 'Family ' . $student->admission_number,
                'phone' => $student->parent
                    ? ($student->parent->guardian_phone ?? $student->parent->father_phone ?? $student->parent->mother_phone)
                    : null,
                'email' => $student->parent
                    ? ($student->parent->guardian_email ?? $student->parent->father_email ?? $student->parent->mother_email)
                    : null,
            ]);
            $student->update(['family_id' => $family->id]);
            $familiesCreated++;
            $this->line("Created family #{$family->id} for student #{$student->id} ({$student->admission_number})");
        }

        // Step 2: Families without an update link → create one (siblings share one link per family)
        $familiesWithoutLink = Family::whereDoesntHave('updateLink')->get();
        foreach ($familiesWithoutLink as $family) {
            if ($dryRun) {
                $this->line("Would create profile update link for family #{$family->id}");
                $linksCreated++;
                continue;
            }
            FamilyUpdateLink::firstOrCreate(
                ['family_id' => $family->id],
                ['is_active' => true]
            );
            $linksCreated++;
            $this->line("Created profile update link for family #{$family->id}");
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Action', $dryRun ? 'Would do' : 'Done'],
            [
                ['Families created (students without family)', $familiesCreated],
                ['Profile update links created (families missing link)', $linksCreated],
            ]
        );

        return self::SUCCESS;
    }
}

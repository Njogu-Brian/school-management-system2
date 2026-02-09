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
    protected $description = 'Ensure every family has one profile update link; create families for students missing one.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->info('Dry run – no changes will be saved.');
        }

        $familiesCreated = 0;
        $linksCreated = 0;

        // 1) Students without a family → create family and assign (one family per student when missing)
        $studentsWithoutFamily = Student::whereNull('family_id')->orWhereDoesntHave('family')->get();
        foreach ($studentsWithoutFamily as $student) {
            if ($dryRun) {
                $this->line("Would create family for student: {$student->full_name} (ID {$student->id})");
                $familiesCreated++;
                continue;
            }
            $family = Family::create([
                'guardian_name' => $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
            ]);
            $student->update(['family_id' => $family->id]);
            $familiesCreated++;
            $this->line("Created family #{$family->id} for student: {$student->full_name}");
        }

        // 2) Families without an update link → create one (families maintain one link each)
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
                ['Families created (students missing family)', $familiesCreated],
                ['Profile update links created (families missing link)', $linksCreated],
            ]
        );

        return self::SUCCESS;
    }
}

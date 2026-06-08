<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class RepairOrphanStudentFamilies extends Command
{
    protected $signature = 'students:repair-orphan-families {--dry-run : List affected students without changing data}';

    protected $description = 'Repair students whose family_id points to a missing or soft-deleted family row';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = Student::query()
            ->whereNotNull('family_id')
            ->whereDoesntHave('family');

        $count = (int) $query->count();
        if ($count === 0) {
            $this->info('No orphaned family_id references found.');

            return self::SUCCESS;
        }

        $this->warn("Found {$count} student(s) with orphaned family_id.");

        if ($dryRun) {
            $query->select(['id', 'admission_number', 'first_name', 'last_name', 'family_id'])
                ->orderBy('id')
                ->chunk(100, function ($students) {
                    foreach ($students as $student) {
                        $this->line("Student #{$student->id} ({$student->admission_number}) family_id={$student->family_id}");
                    }
                });

            return self::SUCCESS;
        }

        $repaired = 0;
        $query->orderBy('id')->chunkById(100, function ($students) use (&$repaired) {
            foreach ($students as $student) {
                ensure_student_family_record($student);
                $repaired++;
                $this->line("Repaired student #{$student->id} → family #{$student->family_id}");
            }
        });

        $this->info("Repaired {$repaired} student(s).");

        return self::SUCCESS;
    }
}

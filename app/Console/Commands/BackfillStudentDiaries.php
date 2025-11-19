<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;

class BackfillStudentDiaries extends Command
{
    protected $signature = 'diaries:backfill';

    protected $description = 'Ensure every student has a personal diary record';

    public function handle(): int
    {
        $created = 0;

        Student::with('diary')->chunkById(100, function ($students) use (&$created) {
            foreach ($students as $student) {
                if (!$student->diary) {
                    $student->diary()->create();
                    $created++;
                }
            }
        });

        $this->info("Backfill complete. Created {$created} diaries.");

        return Command::SUCCESS;
    }
}


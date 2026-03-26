<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearClassroomSubjectAssignmentsCommand extends Command
{
    protected $signature = 'academics:clear-subject-assignments
                            {--force : Skip confirmation (required in production)}
                            {--include-subject-teacher : Also clear subject_teacher (subject ↔ user links)}
                            {--include-stream-teachers : Also clear stream_teacher pivots}';

    protected $description = 'Remove all class–subject rows (classroom_subjects), legacy classroom_subject, and optionally subject/stream teacher links so you can reassign from scratch.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'This deletes every row in classroom_subjects (all class/subject/teacher assignments). Continue?',
            false
        )) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $includeSubjectTeacher = (bool) $this->option('include-subject-teacher');
        $includeStreamTeachers = (bool) $this->option('include-stream-teachers');

        Schema::disableForeignKeyConstraints();

        try {
            $nClassroomSubjects = 0;
            if (Schema::hasTable('classroom_subjects')) {
                $nClassroomSubjects = (int) DB::table('classroom_subjects')->count();
                DB::table('classroom_subjects')->delete();
            }

            $nLegacy = 0;
            if (Schema::hasTable('classroom_subject')) {
                $nLegacy = (int) DB::table('classroom_subject')->count();
                DB::table('classroom_subject')->delete();
            }

            $nSubjectTeacher = 0;
            if ($includeSubjectTeacher && Schema::hasTable('subject_teacher')) {
                $nSubjectTeacher = (int) DB::table('subject_teacher')->count();
                DB::table('subject_teacher')->delete();
            }

            $nStreamTeacher = 0;
            if ($includeStreamTeachers && Schema::hasTable('stream_teacher')) {
                $nStreamTeacher = (int) DB::table('stream_teacher')->count();
                DB::table('stream_teacher')->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info(sprintf(
            'Cleared classroom_subjects: %d row(s). Legacy classroom_subject: %d row(s).',
            $nClassroomSubjects,
            $nLegacy
        ));

        if ($includeSubjectTeacher) {
            $this->info(sprintf('Cleared subject_teacher: %d row(s).', $nSubjectTeacher));
        }
        if ($includeStreamTeachers) {
            $this->info(sprintf('Cleared stream_teacher: %d row(s).', $nStreamTeacher));
        }

        $this->newLine();
        $this->line('Next: run <fg=cyan>php artisan cbc:sync-rationalized-subjects all --assign</> to recreate class slots, then use Subject Teacher Map to assign staff.');

        return self::SUCCESS;
    }
}

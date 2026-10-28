<?php

namespace App\Console\Commands;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Services\FamilyArchiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Undo whole-school promotions that wrongly graduated Grade 8 into alumni
 * because Grade 9 was flagged is_alumni. Moves those students into Grade 9.
 */
class UndoGrade8FalseAlumniCommand extends Command
{
    protected $signature = 'promotions:undo-grade8-false-alumni
                            {--fix : Apply changes (default is dry-run)}
                            {--from=2026-10-28 : Promotion date on/after}';

    protected $description = 'Restore Grade 8 students wrongly marked alumni by whole-school promote into Grade 9';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $from = (string) $this->option('from');

        $grade8 = Classroom::where('name', 'GRADE 8')->first();
        $grade9 = Classroom::where('name', 'GRADE 9')->first();

        if (! $grade8 || ! $grade9) {
            $this->error('GRADE 8 or GRADE 9 classroom not found.');

            return self::FAILURE;
        }

        $rows = StudentAcademicHistory::query()
            ->where('classroom_id', $grade8->id)
            ->where('promotion_status', 'graduated')
            ->where('notes', 'like', '%Whole-school%')
            ->whereDate('promotion_date', '>=', $from)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No matching Grade 8 whole-school alumni records found.');

            return self::SUCCESS;
        }

        $this->info(($fix ? 'FIXING' : 'DRY-RUN').": {$rows->count()} Grade 8 student(s) → GRADE 9");

        $fixed = 0;
        DB::transaction(function () use ($rows, $grade9, $fix, &$fixed) {
            foreach ($rows as $history) {
                /** @var Student|null $student */
                $student = Student::withAlumni()->whereKey($history->student_id)->first();
                if (! $student) {
                    $this->warn("Missing student #{$history->student_id}, skip history #{$history->id}");
                    continue;
                }

                $newStreamId = $this->matchingStream($history->stream_id, $grade9->id);
                $this->line(sprintf(
                    '  #%d %s (%s): alumni → Grade 9 stream=%s',
                    $student->id,
                    trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                    $student->admission_number,
                    $newStreamId ?? 'null'
                ));

                if (! $fix) {
                    continue;
                }

                $student->update([
                    'is_alumni' => false,
                    'alumni_date' => null,
                    'classroom_id' => $grade9->id,
                    'stream_id' => $newStreamId,
                    'archive' => 0,
                ]);

                $history->update([
                    'promotion_status' => 'promoted',
                    'next_classroom_id' => $grade9->id,
                    'next_stream_id' => $newStreamId,
                    'notes' => trim(($history->notes ?? '').' | Corrected: moved to Grade 9 (was false alumni because Grade 9 flagged is_alumni)'),
                    'is_current' => true,
                ]);

                StudentAcademicHistory::where('student_id', $student->id)
                    ->where('id', '!=', $history->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                app(FamilyArchiveService::class)->onStudentRestored($student->fresh());
                $fixed++;
            }
        });

        if ($fix) {
            $this->info("Restored {$fixed} student(s) into Grade 9.");
        } else {
            $this->comment('Dry-run only. Re-run with --fix to apply.');
        }

        return self::SUCCESS;
    }

    private function matchingStream($oldStreamId, int $newClassroomId): ?int
    {
        if (! $oldStreamId) {
            return null;
        }

        $old = Stream::find($oldStreamId);
        if (! $old) {
            return null;
        }

        $match = Stream::where('classroom_id', $newClassroomId)
            ->where('name', $old->name)
            ->first();

        return $match?->id ? (int) $match->id : null;
    }
}

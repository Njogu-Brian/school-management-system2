<?php

namespace App\Console\Commands;

use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Console\Command;

/**
 * Reopen Grade 8 Term 3 End Term Maths + Science for re-entry after wrong roster marks.
 */
class ReopenGrade8Term3EndtermCommand extends Command
{
    protected $signature = 'exams:reopen-g8-t3-endterm
                            {--year=2026 : Academic year number}
                            {--fix : Apply status changes (default dry-run)}';

    protected $description = 'Reopen Grade 8 Term 3 End Term Mathematics and Science exams for mark re-entry';

    public function handle(): int
    {
        $yearNum = (int) $this->option('year');
        $fix = (bool) $this->option('fix');

        $year = AcademicYear::where('year', $yearNum)->first();
        if (! $year) {
            $this->error("Academic year {$yearNum} not found.");

            return self::FAILURE;
        }

        $term = Term::where('academic_year_id', $year->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%Term 3%')
                    ->orWhere('name', 'like', '%Term3%');
            })
            ->orderBy('id')
            ->first();

        if (! $term) {
            $this->error("Term 3 for {$yearNum} not found.");

            return self::FAILURE;
        }

        $classrooms = Classroom::withoutGlobalScope('offered')
            ->where(function ($q) {
                $q->whereRaw('UPPER(name) LIKE ?', ['%GRADE 8%'])
                    ->orWhereRaw('UPPER(name) LIKE ?', ['%GRADE8%']);
            })
            ->get();

        if ($classrooms->isEmpty()) {
            $this->error('No Grade 8 classroom found.');

            return self::FAILURE;
        }

        $exams = Exam::query()
            ->with(['subject:id,name', 'examType:id,name', 'classroom:id,name', 'stream:id,name'])
            ->where('academic_year_id', $year->id)
            ->where('term_id', $term->id)
            ->whereIn('classroom_id', $classrooms->pluck('id'))
            ->where(function ($q) {
                $q->whereHas('subject', function ($s) {
                    $s->where(function ($inner) {
                        $inner->where('name', 'like', '%Math%')
                            ->orWhere('name', 'like', '%Science%');
                    });
                });
            })
            ->where(function ($q) {
                $q->whereHas('examType', function ($t) {
                    $t->where(function ($inner) {
                        $inner->where('name', 'like', '%End%')
                            ->orWhere('name', 'like', '%END%')
                            ->orWhere('name', 'like', '%endterm%')
                            ->orWhere('name', 'like', '%End Term%');
                    });
                })->orWhere('name', 'like', '%End%')
                    ->orWhere('exam_category', 'like', '%end%');
            })
            ->orderBy('id')
            ->get();

        if ($exams->isEmpty()) {
            $this->warn('No matching Grade 8 Term 3 End Term Maths/Science exams found.');

            return self::SUCCESS;
        }

        $this->info(($fix ? 'REOPENING' : 'DRY-RUN').': '.$exams->count().' exam(s)');

        foreach ($exams as $exam) {
            $subj = $exam->subject?->name ?? '?';
            $type = $exam->examType?->name ?? ($exam->name ?? '?');
            $this->line(sprintf(
                '  exam#%d %s | %s | %s | stream=%s | status=%s',
                $exam->id,
                $exam->classroom?->name,
                $type,
                $subj,
                $exam->stream?->name ?? 'all',
                $exam->status
            ));

            if (! $fix) {
                continue;
            }

            $exam->update([
                'status' => 'marking',
                'locked_at' => null,
                'published_at' => null,
                'publish_result' => false,
            ]);
        }

        if ($fix) {
            $this->info('Exams set to marking. Enter marks for the live Grade 8 roster (after promotions:restore-premature --fix if needed).');
        } else {
            $this->comment('Dry-run only. Re-run with --fix to reopen.');
        }

        return self::SUCCESS;
    }
}

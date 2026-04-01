<?php

namespace App\Console\Commands;

use App\Models\Academics\ExamMark;
use App\Services\Academics\ClassroomGradingService;
use Illuminate\Console\Command;

class RecalculateExamMarkGrades extends Command
{
    protected $signature = 'exam-marks:recalculate-grades
                            {--exam-id= : Only marks for this exam id}
                            {--dry-run : Show counts without saving}';

    protected $description = 'Recompute grade_label and pl_level from raw marks using per-class grading schemes (ClassroomGradingService).';

    public function handle(ClassroomGradingService $grading): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $examId = $this->option('exam-id');

        $query = ExamMark::query()
            ->with(['exam.examType', 'student'])
            ->orderBy('id');

        if ($examId !== null && $examId !== '') {
            $query->where('exam_id', (int) $examId);
        }

        $updated = 0;
        $skippedNoScore = 0;
        $skippedNoClass = 0;
        $skippedNoExam = 0;
        $unchanged = 0;

        $query->chunkById(500, function ($marks) use ($grading, $dryRun, &$updated, &$skippedNoScore, &$skippedNoClass, &$skippedNoExam, &$unchanged) {
            foreach ($marks as $mark) {
                $score = $mark->score_moderated ?? $mark->score_raw;
                if ($score === null || $score === '' || ! is_numeric($score)) {
                    $skippedNoScore++;

                    continue;
                }

                $exam = $mark->exam;
                if (! $exam) {
                    $skippedNoExam++;

                    continue;
                }

                $classroomId = (int) ($exam->classroom_id ?? $mark->student?->classroom_id ?? 0);
                if ($classroomId <= 0) {
                    $skippedNoClass++;

                    continue;
                }

                $maxMarks = (float) ($exam->examType?->default_max_mark ?? $exam->max_marks ?? 100);
                if ($maxMarks <= 0) {
                    $skippedNoClass++;

                    continue;
                }

                $g = $grading->gradeForRawScore((float) $score, $maxMarks, $classroomId);
                $newLabel = $g['label'];
                $newPoints = $g['points'];

                if ((string) ($mark->grade_label ?? '') === (string) ($newLabel ?? '')
                    && (string) ($mark->pl_level ?? '') === (string) ($newPoints ?? '')) {
                    $unchanged++;

                    continue;
                }

                if (! $dryRun) {
                    $mark->grade_label = $newLabel;
                    $mark->pl_level = $newPoints;
                    $mark->saveQuietly();
                }
                $updated++;
            }
        });

        $this->info($dryRun ? '[DRY RUN] Would update: '.$updated : 'Updated: '.$updated);
        $this->line('Unchanged (already correct): '.$unchanged);
        $this->line('Skipped (no numeric score): '.$skippedNoScore);
        $this->line('Skipped (no exam): '.$skippedNoExam);
        $this->line('Skipped (no classroom or invalid max marks): '.$skippedNoClass);

        return self::SUCCESS;
    }
}

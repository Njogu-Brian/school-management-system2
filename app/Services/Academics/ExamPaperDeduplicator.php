<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSession;
use App\Models\Academics\ExamType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamPaperDeduplicator
{
    /**
     * @return array{groups:int, removed:int, marks_moved:int, marks_dropped:int}
     */
    public function dedupe(bool $dryRun = false): array
    {
        $stats = ['groups' => 0, 'removed' => 0, 'marks_moved' => 0, 'marks_dropped' => 0];

        $groups = DB::table('exams')
            ->selectRaw('exam_type_id, academic_year_id, term_id, classroom_id, subject_id, IFNULL(stream_id, 0) as stream_key, COUNT(*) as c')
            ->whereNotNull('subject_id')
            ->whereNotNull('classroom_id')
            ->whereNotNull('exam_type_id')
            ->whereNotNull('academic_year_id')
            ->whereNotNull('term_id')
            ->groupByRaw('exam_type_id, academic_year_id, term_id, classroom_id, subject_id, IFNULL(stream_id, 0)')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $stats['groups'] = $groups->count();

        foreach ($groups as $group) {
            $exams = Exam::query()
                ->where('exam_type_id', $group->exam_type_id)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('term_id', $group->term_id)
                ->where('classroom_id', $group->classroom_id)
                ->where('subject_id', $group->subject_id)
                ->when(
                    (int) $group->stream_key === 0,
                    fn ($q) => $q->whereNull('stream_id'),
                    fn ($q) => $q->where('stream_id', (int) $group->stream_key)
                )
                ->orderBy('id')
                ->get();

            if ($exams->count() < 2) {
                continue;
            }

            $result = $this->mergeGroup($exams, $dryRun);
            $stats['removed'] += $result['removed'];
            $stats['marks_moved'] += $result['marks_moved'];
            $stats['marks_dropped'] += $result['marks_dropped'];
        }

        if (! $dryRun) {
            $this->normalizeSessionNames();
            $this->pruneEmptyDuplicateSessions();
        }

        return $stats;
    }

    /**
     * @param  Collection<int, Exam>  $exams
     * @return array{removed:int, marks_moved:int, marks_dropped:int}
     */
    private function mergeGroup(Collection $exams, bool $dryRun): array
    {
        $markCounts = ExamMark::query()
            ->whereIn('exam_id', $exams->pluck('id'))
            ->selectRaw('exam_id, COUNT(*) as c')
            ->groupBy('exam_id')
            ->pluck('c', 'exam_id');

        $typeName = (string) (ExamType::query()->whereKey($exams->first()->exam_type_id)->value('name') ?? '');

        $keeper = $exams->sortByDesc(function (Exam $exam) use ($markCounts, $typeName) {
            $marks = (int) ($markCounts[$exam->id] ?? 0);
            $nameMatch = ($typeName !== '' && str_contains(mb_strtoupper((string) $exam->name), mb_strtoupper($typeName))) ? 1 : 0;
            $statusScore = match ($exam->canonicalStatus()) {
                'locked' => 4,
                'published' => 3,
                'marking' => 2,
                default => 1,
            };

            return sprintf('%05d-%d-%d-%010d', $marks, $nameMatch, $statusScore, $exam->id);
        })->first();

        $losers = $exams->where('id', '!=', $keeper->id)->values();
        $moved = 0;
        $dropped = 0;

        if ($dryRun) {
            return ['removed' => $losers->count(), 'marks_moved' => 0, 'marks_dropped' => 0];
        }

        DB::transaction(function () use ($keeper, $losers, $typeName, &$moved, &$dropped) {
            $keeper->loadMissing(['classroom', 'subject']);

            if ($typeName !== '' && ! str_contains(mb_strtoupper((string) $keeper->name), mb_strtoupper($typeName))) {
                $betterName = $losers->first(function (Exam $exam) use ($typeName) {
                    return str_contains(mb_strtoupper((string) $exam->name), mb_strtoupper($typeName));
                })?->name;

                if ($betterName) {
                    $keeper->name = $betterName;
                } elseif ($keeper->classroom && $keeper->subject) {
                    $keeper->name = trim($typeName.' — '.$keeper->classroom->name.' — '.$keeper->subject->name);
                }
                $keeper->save();
            }

            $session = ExamSession::findOrCreateForScope(
                (int) $keeper->exam_type_id,
                (int) $keeper->academic_year_id,
                (int) $keeper->term_id,
                (int) $keeper->classroom_id,
                $keeper->stream_id ? (int) $keeper->stream_id : null,
                $typeName !== '' && $keeper->classroom
                    ? ($typeName.' — '.$keeper->classroom->name)
                    : (string) $keeper->name,
                (string) ($keeper->modality ?? 'physical'),
                (float) ($keeper->weight ?? 100),
                optional($keeper->starts_on)?->toDateString(),
                optional($keeper->ends_on)?->toDateString(),
            );
            if ((int) ($keeper->exam_session_id ?? 0) !== (int) $session->id) {
                $keeper->exam_session_id = $session->id;
                $keeper->save();
            }

            foreach ($losers as $loser) {
                $loserMarks = ExamMark::query()->where('exam_id', $loser->id)->get();
                foreach ($loserMarks as $mark) {
                    $exists = ExamMark::withTrashed()
                        ->where('exam_id', $keeper->id)
                        ->where('student_id', $mark->student_id)
                        ->where('subject_id', $mark->subject_id)
                        ->first();

                    if ($exists) {
                        if ($exists->trashed()) {
                            $exists->forceDelete();
                        } else {
                            $mark->forceDelete();
                            $dropped++;
                            continue;
                        }
                    }

                    $mark->exam_id = $keeper->id;
                    $mark->save();
                    $moved++;
                }

                $this->repointChildRows($loser->id, $keeper->id);
                $loser->delete();
            }
        });

        return ['removed' => $losers->count(), 'marks_moved' => $moved, 'marks_dropped' => $dropped];
    }

    private function repointChildRows(int $fromExamId, int $toExamId): void
    {
        // Avoid unique-key collisions on change-batch items; dropping loser refs is safer.
        if (Schema::hasTable('exam_mark_change_items') && Schema::hasColumn('exam_mark_change_items', 'exam_id')) {
            DB::table('exam_mark_change_items')->where('exam_id', $fromExamId)->delete();
        }

        foreach (['exam_papers', 'exam_class_subject', 'exam_items'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'exam_id')) {
                continue;
            }
            DB::table($table)->where('exam_id', $fromExamId)->update(['exam_id' => $toExamId]);
        }
    }

    private function normalizeSessionNames(): void
    {
        $sessions = ExamSession::query()->with('examType', 'classroom')->get();
        foreach ($sessions as $session) {
            $typeName = $session->examType?->name;
            $className = $session->classroom?->name;
            if (! $typeName || ! $className) {
                continue;
            }
            $expected = $typeName.' — '.$className;
            if ($session->name !== $expected && ! str_contains(mb_strtoupper((string) $session->name), mb_strtoupper($typeName))) {
                $session->name = $expected;
                $session->save();
            }
        }
    }

    private function pruneEmptyDuplicateSessions(): void
    {
        $empty = ExamSession::query()
            ->whereDoesntHave('papers')
            ->pluck('id');
        if ($empty->isNotEmpty()) {
            ExamSession::query()->whereIn('id', $empty)->delete();
        }
    }

    public function existingPaperId(
        int $examTypeId,
        int $academicYearId,
        int $termId,
        int $classroomId,
        int $subjectId,
        ?int $streamId = null,
    ): ?int {
        return Exam::query()
            ->where('exam_type_id', $examTypeId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId), fn ($q) => $q->whereNull('stream_id'))
            ->value('id');
    }
}

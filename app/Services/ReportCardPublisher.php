<?php

namespace App\Services;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use Illuminate\Support\Collection;

class ReportCardPublisher
{
    public function __construct(
        protected ReportCardBatchService $batchService
    ) {
    }

    /**
     * Push the supplied exam's marks into report cards by regenerating
     * the relevant class/stream combinations for the exam term.
     *
     * @return array{updated:int,groups:int}
     */
    public function pushExam(Exam $exam): array
    {
        $marks = ExamMark::with('student:id,classroom_id,stream_id')
            ->where('exam_id', $exam->id)
            ->get()
            ->filter(fn ($mark) => $mark->student !== null);

        if ($marks->isEmpty()) {
            return ['updated' => 0, 'groups' => 0];
        }

        $groups = $this->groupMarksByClassAndStream($marks);

        $updated = 0;
        foreach ($groups as $group) {
            $updated += $this->batchService->generateForClass(
                academicYearId: $exam->academic_year_id,
                termId: $exam->term_id,
                classroomId: $group['classroom_id'],
                streamId: $group['stream_id']
            );
        }

        return [
            'updated' => $updated,
            'groups'  => $groups->count(),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int,\App\Models\Academics\ExamMark> $marks
     * @return \Illuminate\Support\Collection<int,array{classroom_id:int,stream_id:?int}>
     */
    protected function groupMarksByClassAndStream(Collection $marks): Collection
    {
        return $marks
            ->map(function (ExamMark $mark) {
                return [
                    'classroom_id' => (int) $mark->student->classroom_id,
                    'stream_id'    => $mark->student->stream_id ? (int) $mark->student->stream_id : null,
                ];
            })
            ->filter(fn ($data) => $data['classroom_id'] > 0)
            ->unique(function ($data) {
                return $data['classroom_id'].'|'.($data['stream_id'] ?? 'null');
            })
            ->values();
    }
}

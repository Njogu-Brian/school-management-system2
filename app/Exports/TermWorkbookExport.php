<?php

namespace App\Exports;

use App\Models\Academics\Classroom;
use App\Services\Academics\ExamReports\ClassSheetBuilder;
use App\Services\Academics\ExamReports\ExamScopeResolver;
use App\Services\Academics\ExamReports\ParallelClassroomResolver;
use App\Services\Academics\ExamReports\SchoolWideTeacherRankingService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TermWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $academicYearId,
        private readonly int $termId,
        /** @var array<int> */
        private readonly array $classroomIds = [],
        private readonly string $generatedBy = 'System',
    ) {}

    public function sheets(): array
    {
        $builder = new ClassSheetBuilder();
        $examScope = new ExamScopeResolver();
        $sectionResolver = new ParallelClassroomResolver();
        $usedTitles = [];
        $sheets = [];

        $teacherPayload = (new SchoolWideTeacherRankingService())->rankingsForTerm(
            $this->academicYearId,
            $this->termId
        );
        if (! empty($teacherPayload['per_teacher']) || ! empty($teacherPayload['per_subject_teacher'])) {
            $sheets[] = new TeacherPerformanceExport(
                $teacherPayload,
                $this->uniqueTitle('All Teachers Term', $usedTitles),
                $this->generatedBy
            );
        }

        $classrooms = Classroom::query()
            ->when($this->classroomIds !== [], fn ($q) => $q->whereIn('id', $this->classroomIds))
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        foreach ($classrooms as $classroom) {
            $sections = $this->sectionsForClassroom($classroom, $sectionResolver);

            foreach ($sections as $section) {
                $streamId = $section['stream_id'];
                $label = $section['label'];

                if ($examScope->papersForTerm($this->academicYearId, $this->termId, $classroom->id, $streamId)->isEmpty()) {
                    continue;
                }

                $sessions = $examScope->coreExamSessionsForTerm(
                    $this->academicYearId,
                    $this->termId,
                    $classroom->id,
                    $streamId
                );

                foreach ($sessions as $session) {
                    $session->loadMissing('examType');
                    $payload = $builder->buildForExamSession($session, $classroom, $streamId);
                    if (empty($payload['rows'])) {
                        continue;
                    }
                    $sittingLabel = trim((string) ($session->examType?->name ?? $session->name ?? 'Exam'));
                    $sheets[] = new ClassSheetExport(
                        $payload,
                        $this->uniqueTitle($label.' '.$sittingLabel, $usedTitles),
                        $this->generatedBy
                    );
                }

                $termPayload = $builder->buildForTerm(
                    $this->academicYearId,
                    $this->termId,
                    $classroom,
                    $streamId
                );
                if (! empty($termPayload['rows'])) {
                    $sheets[] = new ClassSheetExport(
                        $termPayload,
                        $this->uniqueTitle($label.' Term', $usedTitles),
                        $this->generatedBy
                    );
                }
            }
        }

        return $sheets;
    }

    /**
     * @return list<array{stream_id: ?int, label: string}>
     */
    private function sectionsForClassroom(Classroom $classroom, ParallelClassroomResolver $resolver): array
    {
        $streams = $resolver->streamsForClassroom($classroom);
        if ($streams->count() >= 2) {
            return $streams->map(fn ($stream) => [
                'stream_id' => (int) $stream->id,
                'label' => trim($classroom->name.' '.$stream->name),
            ])->values()->all();
        }

        return [[
            'stream_id' => null,
            'label' => (string) $classroom->name,
        ]];
    }

    /**
     * @param  array<string, true>  $used
     */
    private function uniqueTitle(string $base, array &$used): string
    {
        $title = mb_substr(trim($base), 0, 31);
        if ($title === '') {
            $title = 'Sheet';
        }

        $n = 2;
        while (isset($used[$title])) {
            $suffix = ' '.$n;
            $title = mb_substr(trim($base), 0, max(1, 31 - mb_strlen($suffix))).$suffix;
            $n++;
        }

        $used[$title] = true;

        return $title;
    }
}

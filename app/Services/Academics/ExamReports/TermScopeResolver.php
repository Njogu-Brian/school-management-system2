<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Exam;
use App\Models\Term;

final class TermScopeResolver
{
    /**
     * Term ids that should match the selected calendar term (same label in year + legacy paper rows).
     *
     * @return list<int>
     */
    public function termIdsForScope(int $selectedTermId, int $academicYearId, ?int $examTypeId = null, ?int $classroomId = null, ?int $streamId = null): array
    {
        $base = $this->termIdsWithSameLabelInYear($selectedTermId, $academicYearId);
        $selected = Term::find($selectedTermId);
        $needleNorm = $selected ? $this->normalizeTermLabel($selected->name) : '';
        if ($needleNorm === '') {
            return $base;
        }

        $paperQuery = Exam::query()
            ->whereNotNull('subject_id')
            ->where('academic_year_id', $academicYearId);

        if ($classroomId) {
            $paperQuery->where('classroom_id', $classroomId);
        }
        if ($examTypeId) {
            $paperQuery->where(function ($q) use ($examTypeId) {
                $q->where('exam_type_id', $examTypeId)
                    ->orWhereHas('examSession', fn ($s) => $s->where('exam_type_id', $examTypeId));
            });
        }
        if ($streamId) {
            $paperQuery->where('stream_id', $streamId);
        } else {
            $paperQuery->whereNull('stream_id');
        }

        $paperTermIds = $paperQuery
            ->distinct()
            ->pluck('term_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $extra = [];
        foreach ($paperTermIds as $tid) {
            if (in_array($tid, $base, true)) {
                continue;
            }
            $t = Term::find($tid);
            if ($t && $this->normalizeTermLabel($t->name) === $needleNorm) {
                $extra[] = $tid;
            }
        }

        $merged = array_values(array_unique(array_merge($base, $extra)));

        return $merged !== [] ? $merged : [$selectedTermId];
    }

    /**
     * @return list<int>
     */
    public function termIdsWithSameLabelInYear(int $termId, int $academicYearId): array
    {
        $term = Term::find($termId);
        if (! $term) {
            return [$termId];
        }

        $needleRaw = mb_strtolower(trim((string) $term->name));
        $needleNorm = $this->normalizeTermLabel($term->name);

        $ids = Term::query()
            ->where('academic_year_id', $academicYearId)
            ->get(['id', 'name'])
            ->filter(function ($t) use ($needleRaw, $needleNorm) {
                $raw = mb_strtolower(trim((string) $t->name));
                if ($raw === $needleRaw) {
                    return true;
                }

                return $needleNorm !== ''
                    && $this->normalizeTermLabel($t->name) === $needleNorm;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids !== [] ? $ids : [$termId];
    }

    /** Strip leading year prefix so "2026 - Term 1" and "Term 1" match. */
    public function normalizeTermLabel(?string $name): string
    {
        $s = mb_strtolower(trim((string) $name));
        $s = preg_replace('/^\d{4}\s*[-–.\/]\s*/u', '', $s);

        return trim($s);
    }

    /**
     * Expand raw exam scopes so UI term pickers match legacy term_id rows on papers.
     *
     * @param  list<array{academic_year_id:int, term_id:int, classroom_id:int}>  $rawScopes
     * @return list<array{academic_year_id:int, term_id:int, classroom_id:int}>
     */
    public function expandTermClassScopes(array $rawScopes): array
    {
        $out = [];
        foreach ($rawScopes as $row) {
            $yearId = (int) $row['academic_year_id'];
            $classId = (int) $row['classroom_id'];
            $termIds = $this->termIdsWithSameLabelInYear((int) $row['term_id'], $yearId);
            foreach ($termIds as $tid) {
                $out[] = [
                    'academic_year_id' => $yearId,
                    'term_id' => $tid,
                    'classroom_id' => $classId,
                ];
            }
        }

        $seen = [];
        $unique = [];
        foreach ($out as $row) {
            $key = $row['academic_year_id'].':'.$row['term_id'].':'.$row['classroom_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $row;
        }

        return $unique;
    }

    /**
     * @param  list<array{subject_id:int, classroom_id:int, academic_year_id:int, term_id:int}>  $rawScopes
     * @return list<array{subject_id:int, classroom_id:int, academic_year_id:int, term_id:int}>
     */
    public function expandSubjectExamScopes(array $rawScopes): array
    {
        $out = [];
        foreach ($rawScopes as $row) {
            $yearId = (int) $row['academic_year_id'];
            $termIds = $this->termIdsWithSameLabelInYear((int) $row['term_id'], $yearId);
            foreach ($termIds as $tid) {
                $out[] = [
                    'subject_id' => (int) $row['subject_id'],
                    'classroom_id' => (int) $row['classroom_id'],
                    'academic_year_id' => $yearId,
                    'term_id' => $tid,
                ];
            }
        }

        $seen = [];
        $unique = [];
        foreach ($out as $row) {
            $key = $row['subject_id'].':'.$row['classroom_id'].':'.$row['academic_year_id'].':'.$row['term_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $row;
        }

        return $unique;
    }
}

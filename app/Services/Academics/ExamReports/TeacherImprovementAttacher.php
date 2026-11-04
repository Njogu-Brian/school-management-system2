<?php

namespace App\Services\Academics\ExamReports;

use Illuminate\Support\Collection;

/**
 * Attach prev_mean / improvement columns by matching stable row keys.
 */
final class TeacherImprovementAttacher
{
    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $currentRows
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $previousRows
     * @return Collection<int, array<string, mixed>>
     */
    public function attach(
        Collection|array $currentRows,
        Collection|array $previousRows,
        string $keyField,
        string $meanField = 'mean'
    ): Collection {
        $current = collect($currentRows)->values();
        $prevByKey = collect($previousRows)
            ->filter(fn ($row) => isset($row[$keyField]))
            ->keyBy(fn ($row) => (string) $row[$keyField]);

        return $current->map(function (array $row) use ($prevByKey, $keyField, $meanField) {
            $key = isset($row[$keyField]) ? (string) $row[$keyField] : null;
            $prev = $key !== null ? $prevByKey->get($key) : null;
            $prevMean = is_array($prev) ? ($prev[$meanField] ?? null) : null;
            $currMean = $row[$meanField] ?? null;

            $row['prev_mean'] = is_numeric($prevMean) ? round((float) $prevMean, 2) : null;
            $row['improvement'] = (is_numeric($currMean) && is_numeric($prevMean))
                ? round((float) $currMean - (float) $prevMean, 2)
                : null;

            return $row;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function topImproved(Collection|array $rows, int $limit = 10): array
    {
        return collect($rows)
            ->filter(fn ($row) => isset($row['improvement']) && is_numeric($row['improvement']))
            ->sortByDesc('improvement')
            ->take($limit)
            ->values()
            ->all();
    }
}

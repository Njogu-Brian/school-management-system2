<?php

namespace App\Services\Academics\ExamReports;

use Illuminate\Support\Collection;

class RowRanker
{
    /**
     * Input rows should contain: total, average, admission_number.
     * Adds: position (ties share same position).
     */
    public function rankByTotal(Collection $rows): Collection
    {
        $sorted = $rows->sort(function ($a, $b) {
            $ta = $a['total'];
            $tb = $b['total'];
            if ($ta === null && $tb === null) return 0;
            if ($ta === null) return 1;
            if ($tb === null) return -1;

            if ($ta === $tb) {
                $aa = $a['average'] ?? -INF;
                $ab = $b['average'] ?? -INF;
                if ($aa === $ab) {
                    return strcmp((string)($a['admission_number'] ?? ''), (string)($b['admission_number'] ?? ''));
                }
                return $aa < $ab ? 1 : -1;
            }
            return $ta < $tb ? 1 : -1;
        })->values();

        $pos = 0;
        $prevTotal = null;
        $prevPos = null;

        return $sorted->map(function ($row) use (&$pos, &$prevTotal, &$prevPos) {
            $pos++;
            if ($row['total'] !== null && $prevTotal !== null && $row['total'] === $prevTotal) {
                $row['position'] = $prevPos;
            } else {
                $row['position'] = $row['total'] === null ? null : $pos;
                $prevTotal = $row['total'];
                $prevPos = $row['position'];
            }
            return $row;
        });
    }
}


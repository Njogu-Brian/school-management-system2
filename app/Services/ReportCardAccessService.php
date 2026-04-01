<?php

namespace App\Services;

use App\Models\Academics\ReportCard;
use App\Models\Invoice;

class ReportCardAccessService
{
    /**
     * Determines if a student can view a report card for a specific term based on fee balance.
     * Returns [allowed, balance].
     */
    public static function canViewPublicReportCard(ReportCard $reportCard): array
    {
        $enforce = (bool) setting('block_results_when_fee_balance', true);
        if (! $enforce) {
            return [true, 0.0];
        }

        $studentId = $reportCard->student_id;
        $yearId = $reportCard->academic_year_id;
        $termId = $reportCard->term_id;

        $balance = (float) Invoice::query()
            ->where('student_id', $studentId)
            ->where('status', '!=', 'reversed')
            ->where(function ($q) use ($yearId) {
                $q->whereNull('academic_year_id')->orWhere('academic_year_id', $yearId);
            })
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id')->orWhere('term_id', $termId);
            })
            ->sum('balance');

        // If invoices haven't been backfilled with academic_year_id/term_id, fall back to legacy year/term ints.
        if ($balance == 0.0) {
            $year = (int) ($reportCard->academicYear?->year ?? 0);
            $termNum = is_numeric($reportCard->term?->name) ? (int) $reportCard->term->name : null;
            if ($year && $termNum) {
                $balance = (float) Invoice::query()
                    ->where('student_id', $studentId)
                    ->where('status', '!=', 'reversed')
                    ->where('year', $year)
                    ->where('term', $termNum)
                    ->sum('balance');
            }
        }

        $allowed = $balance <= 0.0;
        return [$allowed, round($balance, 2)];
    }
}


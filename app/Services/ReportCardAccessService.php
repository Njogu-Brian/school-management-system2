<?php

namespace App\Services;

use App\Models\Academics\ReportCard;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Term;

class ReportCardAccessService
{
    /**
     * Determines if a student can view a report card for a specific term based on fee balance.
     * Returns [allowed, balance].
     */
    public static function canViewPublicReportCard(ReportCard $reportCard): array
    {
        $balance = self::reportTermBalance(
            (int) $reportCard->student_id,
            (int) $reportCard->academic_year_id,
            (int) $reportCard->term_id,
            $reportCard
        );

        $enforce = (bool) setting('block_results_when_fee_balance', true);
        if (! $enforce) {
            return [true, 0.0];
        }

        return [$balance <= 0.0, round($balance, 2)];
    }

    public static function reportTermBalance(
        int $studentId,
        int $yearId,
        int $termId,
        ?ReportCard $reportCard = null
    ): float {
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

        if ($balance == 0.0 && $reportCard) {
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

        return round(max(0.0, $balance), 2);
    }

    public static function nextTerm(?Term $term): ?Term
    {
        if (! $term) {
            return null;
        }

        $closing = $term->closing_date ?? $term->opening_date;
        if ($closing) {
            $next = Term::query()
                ->where('academic_year_id', $term->academic_year_id)
                ->where('id', '!=', $term->id)
                ->whereNotNull('opening_date')
                ->whereDate('opening_date', '>', $closing)
                ->orderBy('opening_date')
                ->first();

            if ($next) {
                return $next;
            }
        }

        $currentYear = $term->academicYear;
        if (! $currentYear) {
            return null;
        }

        $nextYear = AcademicYear::query()
            ->where('year', '>', (int) $currentYear->year)
            ->orderBy('year')
            ->first();

        if (! $nextYear) {
            return null;
        }

        return Term::query()
            ->where('academic_year_id', $nextYear->id)
            ->whereNotNull('opening_date')
            ->orderBy('opening_date')
            ->first();
    }

    /**
     * @return array{
     *   report_term_balance: float,
     *   can_view_report: bool,
     *   invoice_scope: string,
     *   display_term_label: string,
     *   invoices: list<array>,
     *   invoice_total_balance: float
     * }
     */
    public static function billingContextForReportCard(ReportCard $reportCard): array
    {
        $reportCard->loadMissing(['term', 'academicYear', 'student']);

        $reportTermBalance = self::reportTermBalance(
            (int) $reportCard->student_id,
            (int) $reportCard->academic_year_id,
            (int) $reportCard->term_id,
            $reportCard
        );

        [$canView] = self::canViewPublicReportCard($reportCard);

        if ($reportTermBalance > 0.005) {
            $invoices = self::unpaidInvoicesForTerm(
                (int) $reportCard->student_id,
                (int) $reportCard->academic_year_id,
                (int) $reportCard->term_id,
                $reportCard
            );

            return [
                'report_term_balance' => $reportTermBalance,
                'can_view_report' => $canView,
                'invoice_scope' => 'report_term',
                'display_term_label' => trim(($reportCard->term?->name ?? 'Term').' '.($reportCard->academicYear?->year ?? '')),
                'invoices' => $invoices,
                'invoice_total_balance' => round(collect($invoices)->sum('balance'), 2),
            ];
        }

        $nextTerm = self::nextTerm($reportCard->term);
        if ($nextTerm) {
            $invoices = self::unpaidInvoicesForTerm(
                (int) $reportCard->student_id,
                (int) $nextTerm->academic_year_id,
                (int) $nextTerm->id
            );

            $nextYear = $nextTerm->academicYear?->year ?? '';

            return [
                'report_term_balance' => 0.0,
                'can_view_report' => $canView,
                'invoice_scope' => 'next_term',
                'display_term_label' => trim(($nextTerm->name ?? 'Term').' '.$nextYear.' (upcoming)'),
                'invoices' => $invoices,
                'invoice_total_balance' => round(collect($invoices)->sum('balance'), 2),
            ];
        }

        return [
            'report_term_balance' => 0.0,
            'can_view_report' => $canView,
            'invoice_scope' => 'none',
            'display_term_label' => 'Fees up to date',
            'invoices' => [],
            'invoice_total_balance' => 0.0,
        ];
    }

    /**
     * @return list<array{invoice_number: string, year: mixed, term: mixed, total: float, paid_amount: float, balance: float, due_date_label: ?string, status: string, lines: list<array{label: string, balance: float}>}>
     */
    public static function unpaidInvoicesForTerm(
        int $studentId,
        int $yearId,
        int $termId,
        ?ReportCard $reportCard = null
    ): array {
        $query = Invoice::query()
            ->where('student_id', $studentId)
            ->where('status', '!=', 'reversed')
            ->where(function ($q) {
                $q->where('balance', '>', 0.005)
                    ->orWhereIn('status', ['unpaid', 'partial']);
            })
            ->where(function ($q) use ($yearId) {
                $q->whereNull('academic_year_id')->orWhere('academic_year_id', $yearId);
            })
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id')->orWhere('term_id', $termId);
            })
            ->with([
                'items' => fn ($q) => $q->where('status', 'active')->orderBy('id'),
                'items.votehead',
                'items.allocations',
            ])
            ->orderBy('id');

        $invoices = $query->get();

        if ($invoices->isEmpty() && $reportCard) {
            $year = (int) ($reportCard->academicYear?->year ?? 0);
            $termNum = is_numeric($reportCard->term?->name) ? (int) $reportCard->term->name : null;
            if ($year && $termNum) {
                $invoices = Invoice::query()
                    ->where('student_id', $studentId)
                    ->where('status', '!=', 'reversed')
                    ->where(function ($q) {
                        $q->where('balance', '>', 0.005)
                            ->orWhereIn('status', ['unpaid', 'partial']);
                    })
                    ->where('year', $year)
                    ->where('term', $termNum)
                    ->with([
                        'items' => fn ($q) => $q->where('status', 'active')->orderBy('id'),
                        'items.votehead',
                        'items.allocations',
                    ])
                    ->orderBy('id')
                    ->get();
            }
        }

        return $invoices->map(function (Invoice $inv) {
            $lines = [];
            foreach ($inv->items as $item) {
                $allocated = (float) $item->allocations->sum('amount');
                $disc = (float) ($item->discount_amount ?? 0);
                $bal = max(0.0, (float) $item->amount - $disc - $allocated);
                if ($bal < 0.005) {
                    continue;
                }
                $lines[] = [
                    'label' => $item->votehead?->name ?? 'Fee',
                    'balance' => round($bal, 2),
                ];
            }

            return [
                'invoice_number' => $inv->invoice_number ?? ('#'.$inv->id),
                'year' => $inv->year,
                'term' => $inv->term,
                'total' => round((float) $inv->total, 2),
                'paid_amount' => round((float) $inv->paid_amount, 2),
                'balance' => round(max(0.0, (float) $inv->balance), 2),
                'due_date_label' => $inv->due_date ? $inv->due_date->format('d M Y') : null,
                'status' => (string) $inv->status,
                'lines' => $lines,
            ];
        })->filter(fn ($row) => $row['balance'] > 0.005)->values()->all();
    }
}

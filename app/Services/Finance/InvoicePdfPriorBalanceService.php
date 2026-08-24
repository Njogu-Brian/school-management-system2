<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Display-only overlay of earlier unpaid invoices for PDF downloads.
 *
 * This does not persist lines, change stored invoice totals, or affect
 * dashboard / student-invoice / public-link views. Payments continue to
 * allocate to the oldest unpaid invoice first, so a cleared prior invoice
 * simply stops appearing on the next PDF.
 */
class InvoicePdfPriorBalanceService
{
    /**
     * @return array{lines: array<int, array{label: string, invoice_number: string, year: int, term: int, balance: float}>, total: float, pdf_balance_due: float}
     */
    public function overlayForInvoice(Invoice $invoice): array
    {
        $all = $this->overlayForInvoices(collect([$invoice]));

        return $all[$invoice->id] ?? $this->emptyOverlay($invoice);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, array{lines: array<int, array{label: string, invoice_number: string, year: int, term: int, balance: float}>, total: float, pdf_balance_due: float}>
     */
    public function overlayForInvoices(Collection $invoices): array
    {
        $exportIds = $invoices->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $studentIds = $invoices->pluck('student_id')->filter()->unique()->values()->all();

        $priorsByStudent = $studentIds === []
            ? collect()
            : Invoice::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', '!=', 'reversed')
                ->where('balance', '>', 0.009)
                ->when($exportIds !== [], fn ($q) => $q->whereNotIn('id', $exportIds))
                ->orderBy('year')
                ->orderBy('term')
                ->orderBy('id')
                ->get(['id', 'student_id', 'invoice_number', 'year', 'term', 'balance']);

        $priorsByStudent = $priorsByStudent->groupBy(fn (Invoice $inv) => (int) $inv->student_id);

        $out = [];
        foreach ($invoices as $invoice) {
            $out[$invoice->id] = $this->buildOverlay($invoice, $priorsByStudent->get((int) $invoice->student_id, collect()));
        }

        return $out;
    }

    /**
     * @param  Collection<int, Invoice>  $candidates
     * @return array{lines: array<int, array{label: string, invoice_number: string, year: int, term: int, balance: float}>, total: float, pdf_balance_due: float}
     */
    private function buildOverlay(Invoice $invoice, Collection $candidates): array
    {
        $lines = [];
        $total = 0.0;

        foreach ($candidates as $prior) {
            if (! $this->isEarlierThan($prior, $invoice)) {
                continue;
            }

            $balance = round((float) $prior->balance, 2);
            if ($balance <= 0.009) {
                continue;
            }

            $year = (int) $prior->year;
            $term = (int) $prior->term;
            $lines[] = [
                'label' => "Previous unpaid invoice — Term {$term}, {$year}",
                'invoice_number' => (string) ($prior->invoice_number ?? ''),
                'year' => $year,
                'term' => $term,
                'balance' => $balance,
            ];
            $total += $balance;
        }

        $total = round($total, 2);

        return [
            'lines' => $lines,
            'total' => $total,
            'pdf_balance_due' => round((float) $invoice->balance + $total, 2),
        ];
    }

    private function isEarlierThan(Invoice $candidate, Invoice $current): bool
    {
        $currentYear = (int) $current->year;
        $currentTerm = (int) $current->term;
        $priorYear = (int) $candidate->year;
        $priorTerm = (int) $candidate->term;

        if ($priorYear < $currentYear) {
            return true;
        }

        return $priorYear === $currentYear && $priorTerm < $currentTerm;
    }

    /**
     * @return array{lines: array<int, mixed>, total: float, pdf_balance_due: float}
     */
    private function emptyOverlay(Invoice $invoice): array
    {
        return [
            'lines' => [],
            'total' => 0.0,
            'pdf_balance_due' => round((float) $invoice->balance, 2),
        ];
    }
}

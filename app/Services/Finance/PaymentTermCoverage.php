<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Term;
use App\Services\StudentFeeLedgerService;

/**
 * Groups a payment's allocations by academic term so cross-term receipts
 * (e.g. Term 2 arrears + Term 3 invoice) can be labelled distinctly.
 */
class PaymentTermCoverage
{
    /**
     * @param  array<int, array{term_id?:int|string,term_name?:string|null,year?:string|int|null,opening_date?:string|null,amount?:float|int,invoice_number?:string|null}>  $allocationRows
     * @return array{is_cross_term: bool, term_count: int, summary_label: string, terms: array<int, array<string, mixed>>}
     */
    public static function group(array $allocationRows, ?int $currentTermId = null, ?string $currentOpeningDate = null): array
    {
        $byTerm = [];
        foreach ($allocationRows as $row) {
            $termId = (int) ($row['term_id'] ?? 0);
            if ($termId <= 0) {
                continue;
            }
            if (! isset($byTerm[$termId])) {
                $byTerm[$termId] = [
                    'term_id' => $termId,
                    'term_name' => (string) ($row['term_name'] ?? ('Term #'.$termId)),
                    'academic_year' => $row['year'] ?? null,
                    'opening_date' => $row['opening_date'] ?? null,
                    'amount' => 0.0,
                    'invoice_numbers' => [],
                ];
            }
            $byTerm[$termId]['amount'] += (float) ($row['amount'] ?? 0);
            $invoiceNumber = trim((string) ($row['invoice_number'] ?? ''));
            if ($invoiceNumber !== '') {
                $byTerm[$termId]['invoice_numbers'][$invoiceNumber] = true;
            }
        }

        $terms = array_values($byTerm);
        usort($terms, function (array $a, array $b) {
            $dateA = (string) ($a['opening_date'] ?? '');
            $dateB = (string) ($b['opening_date'] ?? '');
            if ($dateA !== '' && $dateB !== '' && $dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            return $a['term_id'] <=> $b['term_id'];
        });

        foreach ($terms as &$term) {
            $term['amount'] = round((float) $term['amount'], 2);
            $term['invoice_numbers'] = array_keys($term['invoice_numbers']);
            $year = $term['academic_year'] ? (string) $term['academic_year'] : '';
            $term['label'] = trim($term['term_name'].($year !== '' ? ' ('.$year.')' : ''));
            $term['role'] = self::roleForTerm($term, $currentTermId, $currentOpeningDate);
            $term['role_label'] = match ($term['role']) {
                'current' => 'Current invoice',
                'previous' => 'Previous balance',
                'upcoming' => 'Upcoming invoice',
                default => 'Invoice',
            };
        }
        unset($term);

        $labels = array_values(array_filter(array_column($terms, 'label')));

        return [
            'is_cross_term' => count($terms) >= 2,
            'term_count' => count($terms),
            'summary_label' => implode(' + ', $labels),
            'terms' => $terms,
        ];
    }

    /**
     * @return array{is_cross_term: bool, term_count: int, summary_label: string, terms: array<int, array<string, mixed>>}
     */
    public static function forPayment(Payment $payment): array
    {
        $rows = self::rowsFromLedger($payment);
        if ($rows === []) {
            $rows = self::rowsFromAllocations($payment);
        }

        $current = function_exists('get_current_term_model') ? get_current_term_model() : null;

        return self::group(
            $rows,
            $current?->id ? (int) $current->id : null,
            optional($current?->opening_date)->toDateString()
        );
    }

    /**
     * @return array<int, array{term_id: int, term_name: string|null, year: mixed, opening_date: string|null, amount: float, invoice_number: string|null}>
     */
    private static function rowsFromLedger(Payment $payment): array
    {
        try {
            $applications = app(StudentFeeLedgerService::class)->applicationsForPayment($payment);
        } catch (\Throwable $e) {
            return [];
        }

        if ($applications === []) {
            return [];
        }

        $invoiceIds = array_values(array_unique(array_map(
            fn (array $row) => (int) ($row['invoice_id'] ?? 0),
            $applications
        )));
        $invoiceIds = array_values(array_filter($invoiceIds, fn (int $id) => $id > 0));
        if ($invoiceIds === []) {
            return [];
        }

        $invoices = Invoice::query()->whereIn('id', $invoiceIds)->get()->keyBy('id');
        $termIds = $invoices->pluck('term_id')->filter()->unique()->values()->all();
        $termsById = empty($termIds)
            ? collect()
            : Term::with('academicYear')->whereIn('id', $termIds)->get()->keyBy('id');

        $rows = [];
        foreach ($applications as $application) {
            $invoice = $invoices->get((int) ($application['invoice_id'] ?? 0));
            if (! $invoice instanceof Invoice || $invoice->isReversed()) {
                continue;
            }
            $termId = (int) ($invoice->term_id ?? 0);
            $rows[] = self::rowFromInvoice(
                $invoice,
                $termsById->get($termId),
                (float) ($application['amount'] ?? 0)
            );
        }

        return $rows;
    }

    /**
     * @return array<int, array{term_id: int, term_name: string|null, year: mixed, opening_date: string|null, amount: float, invoice_number: string|null}>
     */
    private static function rowsFromAllocations(Payment $payment): array
    {
        // Do not nested-eager-load invoice.term: invoices.term is an integer column,
        // so loadMissing()/pluck('term') returns ints and crashes.
        $payment->loadMissing(['allocations.invoiceItem.invoice']);

        $termIds = [];
        foreach ($payment->allocations as $allocation) {
            $invoice = $allocation->invoiceItem?->invoice;
            if (! $invoice instanceof Invoice || $invoice->isReversed()) {
                continue;
            }
            $termId = (int) ($invoice->term_id ?? 0);
            if ($termId > 0) {
                $termIds[$termId] = true;
            }
        }

        $linkedInvoice = null;
        try {
            $linkedInvoice = $payment->invoice;
        } catch (\Throwable $e) {
            $linkedInvoice = null;
        }
        if ($linkedInvoice instanceof Invoice && $linkedInvoice->isReversed()) {
            $linkedInvoice = null;
        }
        $fallbackTermId = (int) ($linkedInvoice?->term_id ?? 0);
        if ($fallbackTermId > 0) {
            $termIds[$fallbackTermId] = true;
        }

        $termsById = empty($termIds)
            ? collect()
            : Term::with('academicYear')->whereIn('id', array_keys($termIds))->get()->keyBy('id');

        $rows = [];
        foreach ($payment->allocations as $allocation) {
            $invoice = $allocation->invoiceItem?->invoice;
            if (! $invoice instanceof Invoice || $invoice->isReversed()) {
                continue;
            }
            $termId = (int) ($invoice->term_id ?? 0);
            if ($termId <= 0) {
                continue;
            }
            $rows[] = self::rowFromInvoice($invoice, $termsById->get($termId), (float) $allocation->amount);
        }

        if ($rows === [] && $linkedInvoice && $fallbackTermId > 0) {
            $rows[] = self::rowFromInvoice(
                $linkedInvoice,
                $termsById->get($fallbackTermId),
                (float) ($payment->allocated_amount ?? $payment->amount)
            );
        }

        return $rows;
    }

    private static function rowFromInvoice(?Invoice $invoice, mixed $term, float $amount): array
    {
        $termModel = is_object($term) ? $term : null;

        return [
            'term_id' => (int) ($invoice?->term_id ?? 0),
            'term_name' => $termModel?->name,
            'year' => $termModel?->academicYear?->year,
            'opening_date' => optional($termModel?->opening_date)->toDateString(),
            'amount' => $amount,
            'invoice_number' => $invoice?->invoice_number,
        ];
    }

    /**
     * @param  array{term_id: int, opening_date?: string|null}  $term
     */
    private static function roleForTerm(array $term, ?int $currentTermId, ?string $currentOpeningDate): string
    {
        if ($currentTermId && (int) $term['term_id'] === $currentTermId) {
            return 'current';
        }

        $opening = (string) ($term['opening_date'] ?? '');
        if ($currentOpeningDate && $opening !== '') {
            if ($opening < $currentOpeningDate) {
                return 'previous';
            }
            if ($opening > $currentOpeningDate) {
                return 'upcoming';
            }
        }

        if ($currentTermId) {
            return (int) $term['term_id'] < $currentTermId ? 'previous' : 'upcoming';
        }

        return 'invoice';
    }
}

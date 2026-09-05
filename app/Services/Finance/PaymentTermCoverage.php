<?php

namespace App\Services\Finance;

use App\Models\Payment;

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
        $payment->loadMissing([
            'allocations.invoiceItem.invoice.term.academicYear',
            'invoice.term.academicYear',
        ]);

        $rows = [];
        foreach ($payment->allocations as $allocation) {
            $invoice = $allocation->invoiceItem?->invoice;
            $term = $invoice?->term;
            $termId = (int) ($invoice?->term_id ?? 0);
            if ($termId <= 0) {
                continue;
            }
            $rows[] = [
                'term_id' => $termId,
                'term_name' => $term?->name,
                'year' => $term?->academicYear?->year,
                'opening_date' => optional($term?->opening_date)->toDateString(),
                'amount' => (float) $allocation->amount,
                'invoice_number' => $invoice?->invoice_number,
            ];
        }

        if ($rows === [] && $payment->invoice?->term_id) {
            $term = $payment->invoice->term;
            $rows[] = [
                'term_id' => (int) $payment->invoice->term_id,
                'term_name' => $term?->name,
                'year' => $term?->academicYear?->year,
                'opening_date' => optional($term?->opening_date)->toDateString(),
                'amount' => (float) ($payment->allocated_amount ?? $payment->amount),
                'invoice_number' => $payment->invoice->invoice_number,
            ];
        }

        $current = function_exists('get_current_term_model') ? get_current_term_model() : null;

        return self::group(
            $rows,
            $current?->id ? (int) $current->id : null,
            optional($current?->opening_date)->toDateString()
        );
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

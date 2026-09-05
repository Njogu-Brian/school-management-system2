<?php

namespace App\Services;

use App\Models\FeeConcession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Chronological fee statement: charges vs payments with a running balance.
 *
 * Invoice lines are the billed amount. Credit notes, debit notes and discounts
 * appear under the invoice with dates. Each payment stores the balance as at
 * that payment so reprints never change when a later payment arrives.
 */
class StudentFeeStatementService
{
    public function forStudent(Student $student, ?int $year = null, $termId = null): array
    {
        $events = $this->buildEvents($student, $year, $termId);
        $running = 0.0;
        $transactions = [];
        $totalCharges = 0.0;
        $totalPayments = 0.0;
        $totalDiscounts = 0.0;
        $totalCreditNotes = 0.0;
        $totalDebitNotes = 0.0;
        $seq = 0;

        foreach ($events as $event) {
            $debit = (float) ($event['debit'] ?? 0);
            $credit = (float) ($event['credit'] ?? 0);
            $affects = $event['affects_balance'] ?? true;
            $before = round($running, 2);
            if ($affects) {
                $running = round($running + $debit - $credit, 2);
            }
            $after = round($running, 2);

            $kind = $event['kind'];
            if ($kind === 'invoice') {
                $totalCharges += $debit;
                $totalDiscounts += (float) ($event['discount_total'] ?? 0);
                $totalCreditNotes += (float) ($event['credit_note_total'] ?? 0);
                $totalDebitNotes += (float) ($event['debit_note_total'] ?? 0);
            } elseif ($kind === 'payment') {
                $totalPayments += $credit;
            } elseif ($kind === 'bbf') {
                $totalCharges += $debit;
                $totalPayments += $credit;
            }

            $description = $event['description'];
            if ($kind === 'term_close') {
                $description = trim(($event['term_name'] ?? '').' '.($event['term_year'] ?? '')).' closed at '.$this->money($after);
            } elseif ($kind === 'payment') {
                $description = $event['description'].' — balance as at this payment '.$this->money($after);
            }

            $transactions[] = [
                'id' => ++$seq,
                'date' => $event['date'],
                'type' => $event['type'],
                'kind' => $kind,
                'description' => $description,
                'narration' => $this->narrationFor($event, $description),
                'reference' => $event['reference'] ?? '',
                'votehead' => $event['votehead'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $after,
                'balance_before' => $before,
                'balance_after' => $after,
                'invoice_id' => $event['invoice_id'] ?? null,
                'invoice_item_id' => $event['invoice_item_id'] ?? null,
                'payment_id' => $event['payment_id'] ?? null,
                'model_type' => $event['model_type'] ?? null,
                'model_id' => $event['model_id'] ?? null,
                'entity_type' => $event['entity_type'] ?? $kind,
                'entity_id' => $event['entity_id'] ?? $event['model_id'] ?? null,
                'children' => $event['children'] ?? [],
                'adjustments' => $event['adjustments'] ?? [],
                'term_name' => $event['term_name'] ?? '',
                'term_year' => $event['term_year'] ?? $year,
                'is_child' => false,
                'is_reversal' => false,
            ];
        }

        return [
            'opening_balance' => 0.0,
            'closing_balance' => round($running, 2),
            'total_charges' => round($totalCharges, 2),
            'total_payments' => round($totalPayments, 2),
            'total_discounts' => round($totalDiscounts, 2),
            'total_credit_notes' => round($totalCreditNotes, 2),
            'total_debit_notes' => round($totalDebitNotes, 2),
            'transactions' => $transactions,
        ];
    }

    /**
     * Running balance immediately after this payment, using only events on or before it.
     */
    public function balanceAfterPayment(Payment $payment): array
    {
        if ($payment->balance_after !== null) {
            return [
                'balance_before' => (float) ($payment->balance_before ?? ((float) $payment->balance_after + (float) $payment->amount)),
                'balance_after' => (float) $payment->balance_after,
            ];
        }

        $student = $payment->student;
        if (! $student) {
            return ['balance_before' => 0.0, 'balance_after' => 0.0];
        }

        $pack = $this->forStudent($student);
        foreach ($pack['transactions'] as $row) {
            if (($row['kind'] ?? '') === 'payment' && (int) ($row['payment_id'] ?? 0) === (int) $payment->id) {
                return [
                    'balance_before' => (float) $row['balance_before'],
                    'balance_after' => (float) $row['balance_after'],
                ];
            }
        }

        return [
            'balance_before' => (float) $pack['closing_balance'] + (float) $payment->amount,
            'balance_after' => (float) $pack['closing_balance'],
        ];
    }

    /**
     * Persist balance_before / balance_after on fee payments that do not yet have a snapshot.
     * Existing values stay frozen so later payments cannot rewrite older receipts.
     */
    public function persistPaymentSnapshots(int $studentId): void
    {
        if (! Schema::hasColumn('payments', 'balance_after')) {
            return;
        }

        $student = Student::withoutGlobalScopes()->find($studentId);
        if (! $student) {
            return;
        }

        $pack = $this->forStudent($student);
        Payment::withoutEvents(function () use ($pack) {
            foreach ($pack['transactions'] as $row) {
                if (($row['kind'] ?? '') !== 'payment' || empty($row['payment_id'])) {
                    continue;
                }
                Payment::where('id', $row['payment_id'])
                    ->whereNull('balance_after')
                    ->update([
                        'balance_before' => $row['balance_before'],
                        'balance_after' => $row['balance_after'],
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    private function buildEvents(Student $student, ?int $year = null, $termId = null): array
    {
        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'reversed')
            ->whereNull('reversed_at')
            ->with([
                'items.votehead',
                'items.creditNotes',
                'items.debitNotes',
                'term.academicYear',
                'creditNotes',
                'debitNotes',
            ])
            ->when($year, function ($q) use ($year) {
                $q->where(function ($q2) use ($year) {
                    $q2->where('year', $year)
                        ->orWhereHas('academicYear', fn ($q3) => $q3->where('year', $year));
                });
            })
            ->when($termId, function ($q) use ($termId) {
                $q->where(function ($q2) use ($termId) {
                    $q2->where('term_id', $termId)
                        ->orWhereHas('term', function ($q3) use ($termId) {
                            $q3->where('id', $termId)->orWhere('name', 'like', "%{$termId}%");
                        });
                });
            })
            ->orderBy('issued_date')
            ->orderBy('id')
            ->get();

        $invoiceIds = $invoices->pluck('id')->all();

        $payments = Payment::query()
            ->where('student_id', $student->id)
            ->where('reversed', false)
            ->where(function ($q) {
                $q->whereNull('receipt_number')->orWhere('receipt_number', 'not like', 'SWIM-%');
            })
            ->with(['paymentMethod', 'allocations.invoiceItem.invoice'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Payment $p) => StudentFeeLedgerService::isFeePayment($p));

        if ($year && $invoiceIds) {
            $payments = $payments->filter(function (Payment $p) use ($year, $invoiceIds) {
                $payYear = $p->payment_date ? Carbon::parse($p->payment_date)->year : null;

                return $payYear === (int) $year || ($p->invoice_id && in_array($p->invoice_id, $invoiceIds, true));
            })->values();
        } elseif ($year) {
            $payments = $payments->filter(function (Payment $p) use ($year) {
                return $p->payment_date && Carbon::parse($p->payment_date)->year === (int) $year;
            })->values();
        }

        $concessions = FeeConcession::query()
            ->where('student_id', $student->id)
            ->where('approval_status', 'approved')
            ->when($year, fn ($q) => $q->where('year', $year))
            ->with('discountTemplate')
            ->orderBy('start_date')
            ->get();
        $concessionsByInvoice = $concessions->groupBy('invoice_id');

        $events = [];

        $bbf = StudentBalanceService::getBalanceBroughtForward($student);
        $hasBbfItem = $invoices->flatMap->items->contains(function ($item) {
            return ($item->source ?? null) === 'balance_brought_forward'
                || (($item->votehead->code ?? null) === 'BAL_BF');
        });
        $bbfYear = $year ?: 2026;
        if ($bbfYear >= 2026 && ! $hasBbfItem && abs((float) $bbf) >= 0.01) {
            $isDebit = (float) $bbf > 0;
            $events[] = [
                'kind' => 'bbf',
                'type' => 'Balance Brought Forward',
                'date' => Carbon::createFromDate((int) $year, 1, 1)->startOfDay(),
                'debit' => $isDebit ? abs((float) $bbf) : 0,
                'credit' => $isDebit ? 0 : abs((float) $bbf),
                'description' => $isDebit
                    ? 'Balance brought forward from '.($year - 1)
                    : 'Overpayment brought forward from '.($year - 1),
                'reference' => 'BBF-'.($year - 1),
                'votehead' => 'Balance Brought Forward',
                'model_type' => 'LegacyStatementTerm',
                'entity_type' => 'bbf',
                'term_year' => $year,
            ];
        }

        foreach ($invoices as $invoice) {
            $items = $invoice->items->filter(function ($item) {
                return ($item->status ?? 'active') === 'active'
                    && ($item->source ?? null) !== 'swimming_attendance';
            });

            $creditNotes = $this->notesForInvoice($invoice, 'credit');
            $debitNotes = $this->notesForInvoice($invoice, 'debit');
            $itemAmounts = (float) $items->sum('amount');
            $itemDiscounts = (float) $items->sum('discount_amount');
            $invoiceDiscount = (float) ($invoice->discount_amount ?? 0);
            $discountTotal = round($itemDiscounts + $invoiceDiscount, 2);
            $net = round($itemAmounts - $discountTotal, 2);

            $term = $invoice->relationLoaded('term') && is_object($invoice->getRelation('term'))
                ? $invoice->getRelation('term')
                : null;
            $termName = is_object($term) ? ($term->name ?? '') : '';
            $termYear = is_object($term) && $term->academicYear ? $term->academicYear->year : ($invoice->year ?? $year);
            $issued = $invoice->issued_date ?? $invoice->created_at;

            $children = [];
            foreach ($items as $item) {
                $vh = $item->votehead->name ?? ($item->custom_votehead_name ?? 'Fee');
                $children[] = [
                    'description' => $vh,
                    'votehead' => $vh,
                    'debit' => (float) $item->amount,
                    'credit' => (float) ($item->discount_amount ?? 0),
                    'invoice_item_id' => $item->id,
                ];
            }

            $adjustments = [];
            foreach ($creditNotes as $note) {
                $adjustments[] = $this->adjustmentRow('credit_note', $note, $invoice);
            }
            foreach ($debitNotes as $note) {
                $adjustments[] = $this->adjustmentRow('debit_note', $note, $invoice);
            }
            if ($discountTotal > 0.009) {
                $adjustments[] = [
                    'kind' => 'discount',
                    'type' => 'Discount',
                    'date' => $issued,
                    'debit' => 0,
                    'credit' => $discountTotal,
                    'description' => 'Discount '.$this->money($discountTotal),
                    'reference' => $invoice->invoice_number,
                ];
            }
            foreach ($concessionsByInvoice->get($invoice->id, collect()) as $c) {
                $adjustments[] = $this->concessionAdjustment($c);
            }

            $billedLabel = $itemAmounts > 0.009 && abs($itemAmounts - $net) > 0.009
                ? ' — billed '.$this->money($itemAmounts)
                : '';

            $events[] = [
                'kind' => 'invoice',
                'type' => 'Invoice',
                'date' => $issued,
                'debit' => max(0, $net),
                'credit' => 0,
                'description' => trim('Invoice '.($invoice->invoice_number ?? '').' '.($termName ?: '').($termYear ? ' ('.$termYear.')' : '')).$billedLabel,
                'reference' => $invoice->invoice_number,
                'votehead' => $termName ?: 'Invoice',
                'invoice_id' => $invoice->id,
                'model_type' => 'Invoice',
                'model_id' => $invoice->id,
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'children' => $children,
                'adjustments' => $adjustments,
                'discount_total' => $discountTotal,
                'credit_note_total' => (float) $creditNotes->sum('amount'),
                'debit_note_total' => (float) $debitNotes->sum('amount'),
                'term_name' => $termName,
                'term_year' => $termYear,
                'term_id' => $invoice->term_id,
                'term_closing' => is_object($term) ? $term->closing_date : null,
            ];
        }

        foreach ($concessions as $c) {
            if ($c->invoice_id && $invoices->contains('id', $c->invoice_id)) {
                continue;
            }
            $events[] = [
                'kind' => 'discount',
                'type' => 'Discount',
                'date' => $c->start_date ?? $c->created_at,
                'debit' => 0,
                'credit' => 0,
                'affects_balance' => false,
                'description' => $this->concessionLabel($c),
                'reference' => 'CON-'.$c->id,
                'votehead' => $c->type ?? 'Discount',
                'invoice_id' => $c->invoice_id,
                'model_type' => 'FeeConcession',
                'model_id' => $c->id,
                'entity_type' => 'discount',
            ];
        }

        foreach ($payments as $payment) {
            $method = $payment->paymentMethod->name ?? $payment->payment_method ?? 'Payment';
            try {
                $coverage = \App\Services\Finance\PaymentTermCoverage::forPayment($payment);
            } catch (\Throwable $e) {
                $coverage = ['is_cross_term' => false, 'summary_label' => ''];
            }
            $description = 'Payment received — '.$method.' '.$this->money((float) $payment->amount);
            if (! empty($coverage['is_cross_term'])) {
                $description .= ' — cross-term ('.$coverage['summary_label'].')';
            }
            $events[] = [
                'kind' => 'payment',
                'type' => ! empty($coverage['is_cross_term']) ? 'Cross-term payment' : 'Payment',
                'date' => $payment->payment_date ?? $payment->created_at,
                'debit' => 0,
                'credit' => (float) $payment->amount,
                'description' => $description,
                'reference' => $payment->receipt_number ?? $payment->transaction_code,
                'votehead' => $method,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'model_type' => 'Payment',
                'model_id' => $payment->id,
                'entity_type' => 'payment',
                'entity_id' => $payment->id,
            ];
        }

        $termCloses = $invoices->map(function (Invoice $invoice) {
            $term = $invoice->relationLoaded('term') && is_object($invoice->getRelation('term'))
                ? $invoice->getRelation('term')
                : null;
            if (! is_object($term) || empty($term->closing_date)) {
                return null;
            }
            $year = $term->academicYear->year ?? $invoice->year;

            return [
                'term_id' => $term->id,
                'name' => $term->name,
                'year' => $year,
                'closing' => Carbon::parse($term->closing_date)->endOfDay(),
            ];
        })->filter()->unique('term_id');

        foreach ($termCloses as $close) {
            if ($close['closing']->gt(now())) {
                continue;
            }
            $events[] = [
                'kind' => 'term_close',
                'type' => 'Term Close',
                'date' => $close['closing'],
                'debit' => 0,
                'credit' => 0,
                'affects_balance' => false,
                'description' => trim($close['name'].' '.$close['year']).' closed',
                'reference' => '',
                'votehead' => $close['name'],
                'term_name' => $close['name'],
                'term_year' => $close['year'],
                'entity_type' => 'term_close',
            ];
        }

        usort($events, function ($a, $b) {
            $da = Carbon::parse($a['date'])->timestamp;
            $db = Carbon::parse($b['date'])->timestamp;
            if ($da !== $db) {
                return $da <=> $db;
            }
            $order = ['bbf' => 0, 'invoice' => 1, 'discount' => 2, 'payment' => 3, 'term_close' => 4];

            return ($order[$a['kind']] ?? 9) <=> ($order[$b['kind']] ?? 9);
        });

        return $events;
    }

    private function notesForInvoice(Invoice $invoice, string $which): Collection
    {
        $rel = $which === 'credit' ? 'creditNotes' : 'debitNotes';
        $fromInvoice = $invoice->relationLoaded($rel) ? $invoice->{$rel} : collect();
        $fromItems = $invoice->items->flatMap(function ($item) use ($rel) {
            return $item->relationLoaded($rel) ? $item->{$rel} : collect();
        });

        return $fromInvoice->concat($fromItems)->unique('id')->filter(function ($note) {
            return empty($note->deleted_at);
        })->values();
    }

    private function adjustmentRow(string $kind, $note, Invoice $invoice): array
    {
        $isCredit = $kind === 'credit_note';
        $vh = optional(optional($note->invoiceItem)->votehead)->name;
        $date = $note->issued_at ?? $note->created_at;

        return [
            'kind' => $kind,
            'type' => $isCredit ? 'Credit Note' : 'Debit Note',
            'date' => $date,
            'debit' => $isCredit ? 0 : (float) $note->amount,
            'credit' => $isCredit ? (float) $note->amount : 0,
            'description' => ($isCredit ? 'Credit note' : 'Debit note')
                .' '.$this->money((float) $note->amount)
                .($vh ? ' — '.$vh : '')
                .($note->reason ? ' ('.$note->reason.')' : ''),
            'reference' => $isCredit
                ? ($note->credit_note_number ?? 'CN-'.$note->id)
                : ($note->debit_note_number ?? 'DN-'.$note->id),
        ];
    }

    private function concessionAdjustment($concession): array
    {
        $date = $concession->start_date ?? $concession->created_at;

        return [
            'kind' => 'discount',
            'type' => 'Discount',
            'date' => $date,
            'debit' => 0,
            'credit' => (float) ($concession->value ?? $concession->amount ?? 0),
            'description' => $this->concessionLabel($concession),
            'reference' => 'CON-'.$concession->id,
        ];
    }

    private function concessionLabel($c): string
    {
        $label = $c->discountTemplate->name ?? $c->reason ?? $c->type ?? 'Concession';
        if (stripos((string) $c->type, 'sibling') !== false || stripos((string) $label, 'sibling') !== false) {
            $label = 'Sibling discount'.($c->reason ? ' — '.$c->reason : '');
        }
        $amount = (float) ($c->value ?? $c->amount ?? 0);
        if ($amount > 0.009) {
            $label .= ' '.$this->money($amount);
        }

        return $label;
    }

    private function narrationFor(array $event, string $description): string
    {
        $lines = [$description];
        foreach ($event['children'] ?? [] as $child) {
            $line = '  '.$child['description'].': '.$this->money((float) ($child['debit'] ?? 0));
            if (($child['credit'] ?? 0) > 0.009) {
                $line .= ' (discount '.$this->money((float) $child['credit']).')';
            }
            $lines[] = $line;
        }
        foreach ($event['adjustments'] ?? [] as $adj) {
            $date = ! empty($adj['date']) ? Carbon::parse($adj['date'])->format('d M Y') : '';
            $lines[] = '  '.$adj['description'].($date ? ' — '.$date : '');
        }

        return implode("\n", $lines);
    }

    private function money(float $amount): string
    {
        return 'Ksh '.number_format($amount, 2);
    }
}

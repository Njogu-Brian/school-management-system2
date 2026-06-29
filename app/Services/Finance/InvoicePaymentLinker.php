<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementLine;
use Carbon\Carbon;

/**
 * Links statement payment lines to already-recorded vendor invoices so the same
 * cost is never counted twice.
 *
 * Example: the Azanet internet invoices seeded as expenses are the actual cost;
 * the M-Pesa "Pay Bill to 4084687 - AZANET" payments are how they were settled.
 * This matches each payment to its invoice (by account, amount and nearest date)
 * and points the statement line's expense_id at the invoice — which makes the
 * submit flow skip it (it only creates expenses for lines with a NULL
 * expense_id), so no duplicate expense is ever created.
 */
class InvoicePaymentLinker
{
    /**
     * Link M-Pesa Azanet paybill (4084687, account 1903) payments to the seeded
     * Azanet internet invoices.
     *
     * @return array{linked:int, payments:int, invoices_available:int}
     */
    public function linkAzanet(?int $importId = null): array
    {
        $internetId = ExpenseCategory::where('code', 'INTERNET')->value('id')
            ?? ExpenseCategory::where('code', 'COMMUNICATION')->value('id');

        // Invoice expenses (the cost) that don't yet have a payment linked to them.
        $invoices = Expense::query()
            ->where('source_type', 'azanet_invoice')
            ->orderBy('expense_date')
            ->get(['id', 'expense_date', 'total']);

        if ($invoices->isEmpty()) {
            return ['linked' => 0, 'payments' => 0, 'invoices_available' => 0];
        }

        $alreadyLinked = ExpenseStatementLine::whereIn('expense_id', $invoices->pluck('id'))
            ->pluck('expense_id')
            ->flip();

        $available = $invoices->reject(fn ($inv) => $alreadyLinked->has($inv->id))->values();

        // Azanet paybill payments for the invoiced account (1903), not yet linked.
        $payments = ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->whereNull('expense_id')
            ->where('paybill_number', '4084687')
            ->where(function ($q) {
                $q->where('account_reference', 'like', '%1903%')
                  ->orWhere('narration', 'like', '%Acc. 1903%')
                  ->orWhere('narration', 'like', '%LTD Acc. 1903%');
            })
            ->when($importId, fn ($q) => $q->where('import_id', $importId))
            ->orderBy('completed_at')
            ->get();

        $linked = 0;

        foreach ($payments as $payment) {
            $payDate = $payment->completed_at ? Carbon::parse($payment->completed_at) : null;
            if (! $payDate) {
                continue;
            }

            $best = null;
            $bestScore = null;
            foreach ($available as $idx => $inv) {
                if ($inv === null) {
                    continue;
                }
                $gap = Carbon::parse($inv->expense_date)->diffInDays($payDate, false);
                // Payment usually lands from a few days before to ~5 weeks after the invoice date.
                if ($gap < -10 || $gap > 40) {
                    continue;
                }
                $amountMiss = abs((float) $inv->total - (float) $payment->withdrawn_amount);
                // Prefer same amount, then nearest date.
                $score = ($amountMiss < 0.01 ? 0 : 100000) + abs($gap);
                if ($bestScore === null || $score < $bestScore) {
                    $bestScore = $score;
                    $best = $idx;
                }
            }

            if ($best === null) {
                continue;
            }

            $invoice = $available[$best];
            $payment->expense_id = $invoice->id;
            $payment->review_status = ExpenseStatementLine::REVIEW_CONFIRMED;
            if (! $payment->expense_category_id && $internetId) {
                $payment->expense_category_id = $internetId;
            }
            if (! trim((string) $payment->vendor_name)) {
                $payment->vendor_name = 'Azanet Solutions Ltd';
            }
            $raw = $payment->raw_data ?? [];
            $raw['linked_invoice'] = $invoice->id;
            $payment->raw_data = $raw;
            $payment->save();

            $available[$best] = null; // consume the invoice
            $linked++;
        }

        return [
            'linked' => $linked,
            'payments' => $payments->count(),
            'invoices_available' => $available->filter()->count() + $linked,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\LegacyFinanceImportBatch;
use App\Models\LegacyLedgerPosting;
use App\Models\LegacyStatementLine;
use App\Models\LegacyStatementTerm;
use App\Models\LegacyVoteheadMapping;
use App\Models\Votehead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LegacyLedgerPostingService
{
    /**
     * Prepare and post a legacy batch into finance tables.
     * Idempotent via legacy_ledger_postings hash.
     */
    public function processBatch(int $batchId): void
    {
        $batch = LegacyFinanceImportBatch::with(['terms', 'terms.lines'])->find($batchId);
        if (!$batch) {
            Log::warning('Legacy posting skipped: batch not found', ['batch_id' => $batchId]);
            return;
        }

        $missing = $this->ensureVoteheadMappings($batch);
        if ($missing->isNotEmpty()) {
            Log::info('Legacy posting halted: votehead mappings required', [
                'batch_id' => $batch->id,
                'missing' => $missing->values(),
            ]);
            return;
        }

        foreach ($batch->terms as $term) {
            $this->processTerm($batch, $term);
        }
    }

    /**
     * Find missing voteheads and create pending mapping records.
     */
    public function ensureVoteheadMappings(LegacyFinanceImportBatch $batch): Collection
    {
        $labels = $batch->terms
            ->flatMap(fn (LegacyStatementTerm $term) => $term->lines->pluck('votehead'))
            ->filter()
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        $missing = collect();

        foreach ($labels as $label) {
            $resolved = Votehead::whereRaw('LOWER(name) = ?', [mb_strtolower($label, 'UTF-8')])->first();
            $mapping = LegacyVoteheadMapping::where('legacy_label', $label)->first();

            if ($resolved && (!$mapping || !$mapping->votehead_id)) {
                LegacyVoteheadMapping::updateOrCreate(
                    ['legacy_label' => $label],
                    ['votehead_id' => $resolved->id, 'status' => 'resolved']
                );
                continue;
            }

            if ($mapping && $mapping->status === 'resolved') {
                continue;
            }

            LegacyVoteheadMapping::firstOrCreate(
                ['legacy_label' => $label],
                ['status' => 'pending']
            );

            $missing->push($label);
        }

        return $missing;
    }

    /**
     * Compute a stable hash for a line to guard idempotency.
     */
    public function hashLine(LegacyStatementLine $line): string
    {
        return hash('sha256', implode('|', [
            $line->id,
            $line->term_id,
            $line->txn_type,
            $line->txn_date,
            $line->narration_raw,
            $line->amount_dr,
            $line->amount_cr,
            $line->running_balance,
            $line->votehead,
        ]));
    }

    /**
     * Record a posting attempt to keep idempotency.
     */
    public function recordPosting(
        LegacyStatementLine $line,
        string $targetType,
        ?int $targetId,
        string $status = 'posted',
        ?string $error = null
    ): LegacyLedgerPosting {
        return LegacyLedgerPosting::create([
            'batch_id' => $line->batch_id,
            'term_id' => $line->term_id,
            'line_id' => $line->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'hash' => $this->hashLine($line),
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    /**
     * Process a single term into finance tables.
     */
    protected function processTerm(LegacyFinanceImportBatch $batch, LegacyStatementTerm $term): void
    {
        if (!$term->student_id) {
            Log::warning('Legacy posting skipped term: missing student mapping', [
                'batch_id' => $batch->id,
                'term_id' => $term->id,
                'admission' => $term->admission_number,
            ]);
            return;
        }

        $lines = $term->lines;
        if ($lines->isEmpty()) {
            return;
        }

        // Build invoice lines grouped by votehead
        $invoiceLines = $lines->filter(fn ($l) => $l->txn_type === 'invoice');
        $discountLines = $lines->filter(fn ($l) => $this->isDiscount($l));
        $creditLines = $lines->filter(fn ($l) => $l->txn_type === 'credit_note' && !$this->isDiscount($l));
        $debitLines = $lines->filter(fn ($l) => $l->txn_type === 'debit_note');
        $receiptLines = $lines->filter(fn ($l) => $l->txn_type === 'receipt');
        $opening = $term->starting_balance;

        $invoiceDate = $this->determineInvoiceDate($invoiceLines, $receiptLines, $batch);
        $invoice = null;

        if ($invoiceLines->isNotEmpty()) {
            $invoice = $this->createInvoice($term, $invoiceLines, $invoiceDate);
            $this->createInvoiceItems($invoice, $invoiceLines);
            if ($opening !== null) {
                $this->createOpeningBalanceItem($invoice, (float) $opening);
            }
            $invoice->recalculate();
        }

        foreach ($receiptLines as $line) {
            $this->createPayment($term, $invoice, $line);
        }

        foreach ($discountLines as $line) {
            $this->createCreditNote($invoice, $line, 'Discount');
        }

        foreach ($creditLines as $line) {
            $this->createCreditNote($invoice, $line);
        }

        foreach ($debitLines as $line) {
            $this->createDebitNote($invoice, $line);
        }
    }

    protected function determineInvoiceDate(Collection $invoiceLines, Collection $receiptLines, LegacyFinanceImportBatch $batch): \Carbon\Carbon
    {
        $dates = $invoiceLines->pluck('txn_date')->filter()->sort()->values();
        if ($dates->isEmpty()) {
            $dates = $receiptLines->pluck('txn_date')->filter()->sort()->values();
        }
        return $dates->first() ?: ($batch->created_at ?? now());
    }

    protected function resolveVoteheadId(string $label): ?int
    {
        $mapping = LegacyVoteheadMapping::where('legacy_label', $label)->first();
        return $mapping?->votehead_id;
    }

    protected function createInvoice(LegacyStatementTerm $term, Collection $invoiceLines, \Carbon\Carbon $date)
    {
        $firstRef = $invoiceLines->firstWhere('reference_number', '!=', null)?->reference_number;
        $number = $this->uniqueInvoiceNumber($firstRef);

        return \App\Models\Invoice::create([
            'student_id' => $term->student_id,
            'invoice_number' => $number,
            'issued_date' => $date,
            'due_date' => $date,
            'status' => 'unpaid',
            'total' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'notes' => 'Imported from legacy batch ' . $term->batch_id,
        ]);
    }

    protected function createInvoiceItems($invoice, Collection $invoiceLines): void
    {
        $groups = $invoiceLines->groupBy(function ($line) {
            return trim((string) $line->votehead) ?: 'UNSPECIFIED';
        });

        foreach ($groups as $label => $lines) {
            $voteheadId = $this->resolveVoteheadId($label);
            $amount = $lines->sum(fn ($l) => (float) ($l->amount_dr ?? 0));
            if ($amount <= 0) {
                continue;
            }
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $voteheadId,
                'amount' => $amount,
                'discount_amount' => 0,
                'original_amount' => $amount,
                'status' => 'active',
                'source' => 'legacy_import',
                'effective_date' => $invoice->issued_date,
            ]);
        }
    }

    protected function createOpeningBalanceItem($invoice, float $amount): void
    {
        if ($amount === 0) {
            return;
        }
        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'votehead_id' => null,
            'amount' => $amount,
            'discount_amount' => 0,
            'original_amount' => $amount,
            'status' => 'active',
            'source' => 'legacy_opening',
            'effective_date' => $invoice->issued_date,
        ]);
    }

    protected function createPayment(LegacyStatementTerm $term, $invoice, LegacyStatementLine $line): void
    {
        $hash = $this->hashLine($line);
        if (LegacyLedgerPosting::where('hash', $hash)->where('target_type', 'payment')->exists()) {
            return;
        }

        $txnCode = $line->txn_code ?: $line->reference_number ?: null;
        if ($txnCode && \App\Models\Payment::where('transaction_code', $txnCode)->exists()) {
            $this->recordPosting($line, 'payment', null, 'skipped', 'Duplicate transaction_code');
            return;
        }

        $payment = \App\Models\Payment::create([
            'student_id' => $term->student_id,
            'invoice_id' => $invoice?->id,
            'amount' => (float) ($line->amount_cr ?? 0),
            'allocated_amount' => 0,
            'unallocated_amount' => (float) ($line->amount_cr ?? 0),
            'payment_method_id' => $this->mapChannelToPaymentMethod($line->channel),
            'payer_name' => $term->student_name,
            'narration' => $line->narration_raw,
            'payment_date' => $line->txn_date ?? now(),
            'transaction_code' => $txnCode,
        ]);

        // Allocate if invoice exists
        if ($invoice) {
            $allocAmount = min($payment->amount, $invoice->balance);
            if ($allocAmount > 0) {
                $item = $invoice->items()->first();
                if ($item) {
                    \App\Models\PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_item_id' => $item->id,
                        'amount' => $allocAmount,
                        'allocated_at' => now(),
                    ]);
                    $payment->allocated_amount = $allocAmount;
                    $payment->unallocated_amount = max(0, $payment->amount - $allocAmount);
                    $payment->save();
                    $invoice->recalculate();
                }
            }
        }

        $this->recordPosting($line, 'payment', $payment->id);
    }

    protected function createCreditNote($invoice, LegacyStatementLine $line, string $reason = null): void
    {
        if (!$invoice) {
            return;
        }

        $hash = $this->hashLine($line);
        if (LegacyLedgerPosting::where('hash', $hash)->where('target_type', 'credit')->exists()) {
            return;
        }

        $ref = $line->reference_number ?: $line->txn_code;
        if ($ref && \App\Models\CreditNote::where('credit_note_number', $ref)->exists()) {
            $this->recordPosting($line, 'credit', null, 'skipped', 'Duplicate credit note number');
            return;
        }

        $amount = (float) ($line->amount_cr ?? 0);
        if ($amount <= 0) {
            return;
        }

        $credit = \App\Models\CreditNote::create([
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $invoice->items()->first()?->id,
            'amount' => $amount,
            'reason' => $reason ?: 'Legacy credit note',
            'notes' => $line->narration_raw,
            'credit_note_number' => $ref,
            'issued_at' => $line->txn_date ?? now(),
        ]);

        // Adjust invoice by adding a negative line via debit/credit? Simplify: reduce item amount
        $item = $invoice->items()->first();
        if ($item) {
            $item->amount = max(0, $item->amount - $amount);
            $item->save();
        }
        $invoice->recalculate();

        $this->recordPosting($line, 'credit', $credit->id);
    }

    protected function createDebitNote($invoice, LegacyStatementLine $line): void
    {
        if (!$invoice) {
            return;
        }

        $hash = $this->hashLine($line);
        if (LegacyLedgerPosting::where('hash', $hash)->where('target_type', 'debit')->exists()) {
            return;
        }

        $ref = $line->reference_number ?: $line->txn_code;
        if ($ref && \App\Models\DebitNote::where('debit_note_number', $ref)->exists()) {
            $this->recordPosting($line, 'debit', null, 'skipped', 'Duplicate debit note number');
            return;
        }

        $amount = (float) ($line->amount_dr ?? 0);
        if ($amount <= 0) {
            return;
        }

        $debit = \App\Models\DebitNote::create([
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $invoice->items()->first()?->id,
            'amount' => $amount,
            'reason' => 'Legacy debit note',
            'notes' => $line->narration_raw,
            'debit_note_number' => $ref,
            'issued_at' => $line->txn_date ?? now(),
        ]);

        $item = $invoice->items()->first();
        if ($item) {
            $item->amount += $amount;
            $item->save();
        }
        $invoice->recalculate();

        $this->recordPosting($line, 'debit', $debit->id);
    }

    protected function mapChannelToPaymentMethod(?string $channel): ?int
    {
        $ch = strtoupper((string) $channel);
        $mpesa = \App\Models\PaymentMethod::where(function ($q) {
            $q->where('name', 'like', '%SAF_MPESA%')
              ->orWhere('name', 'like', '%MPESA%');
        })->first();
        $bank = \App\Models\PaymentMethod::where(function ($q) {
            $q->where('name', 'like', '%PESALINK_EQUITY%')
              ->orWhere('name', 'like', '%BANK%');
        })->first();

        if (str_contains($ch, 'MPESA') || str_contains($ch, 'CASH') || !$channel) {
            return $mpesa?->id;
        }
        if (str_contains($ch, 'BANK') || str_contains($ch, 'PESALINK')) {
            return $bank?->id ?? $mpesa?->id;
        }
        return $mpesa?->id;
    }

    protected function uniqueInvoiceNumber(?string $preferred): string
    {
        if ($preferred && !\App\Models\Invoice::where('invoice_number', $preferred)->exists()) {
            return $preferred;
        }
        return \App\Services\DocumentNumberService::generate('invoice', 'INV');
    }

    protected function isDiscount(LegacyStatementLine $line): bool
    {
        return stripos((string) $line->narration_raw, 'discount') !== false;
    }
}


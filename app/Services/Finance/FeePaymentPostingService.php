<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;

class FeePaymentPostingService
{
    public function __construct(
        protected JournalPostingService $journalPosting,
    ) {}

    public function shouldSkip(Payment $payment): bool
    {
        if ($payment->reversed) {
            return true;
        }

        if (str_starts_with((string) $payment->receipt_number, 'SWIM-')) {
            return true;
        }

        return (float) $payment->amount <= 0;
    }

    public function post(Payment $payment, ?User $user = null): ?JournalEntry
    {
        if ($this->shouldSkip($payment) || $payment->journal_entry_id) {
            return $payment->journalEntry;
        }

        $payment->loadMissing(['allocations.invoiceItem.votehead.account', 'paymentMethod.bankAccount.account']);

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $cashAccount = $this->resolveCashAccount($payment);
        $credits = $this->buildCreditLines($payment, $amount);

        $lines = array_merge(
            [['account_id' => $cashAccount->id, 'debit' => $amount, 'description' => 'Fee receipt ' . ($payment->receipt_number ?? $payment->transaction_code)]],
            $credits,
        );

        $entry = $this->journalPosting->post(
            $lines,
            'Fee payment ' . ($payment->receipt_number ?? $payment->transaction_code),
            $payment->payment_date ?? now(),
            'payment',
            $payment->id,
            $user ?? auth()->user(),
        );

        $payment->journal_entry_id = $entry->id;
        $payment->saveQuietly();

        return $entry;
    }

    public function reverse(Payment $payment, ?User $user = null): ?JournalEntry
    {
        if (! $payment->journal_entry_id) {
            return null;
        }

        $original = JournalEntry::with('lines')->find($payment->journal_entry_id);
        if (! $original) {
            return null;
        }

        $reversalLines = $original->lines->map(fn (JournalLine $line) => [
            'account_id' => $line->account_id,
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
            'description' => 'Reversal of ' . $original->entry_no,
        ])->all();

        $entry = $this->journalPosting->post(
            $reversalLines,
            'Reversal of fee payment ' . ($payment->receipt_number ?? $payment->transaction_code),
            now(),
            'payment_reversal',
            $payment->id,
            $user ?? auth()->user(),
        );

        $payment->journal_entry_id = null;
        $payment->saveQuietly();

        return $entry;
    }

    protected function resolveCashAccount(Payment $payment): Account
    {
        $bankGl = $payment->paymentMethod?->bankAccount?->account;
        if ($bankGl) {
            return $bankGl;
        }

        $methodName = strtolower((string) ($payment->paymentMethod->name ?? $payment->payment_method ?? ''));

        if (str_contains($methodName, 'cash')) {
            return $this->journalPosting->systemAccount('1000');
        }

        return $this->journalPosting->systemAccount('1011');
    }

    /**
     * @return array<int, array{account_id: int, credit: float, description?: string}>
     */
    protected function buildCreditLines(Payment $payment, float $paymentAmount): array
    {
        $defaultRevenue = $this->journalPosting->systemAccount('4000');
        $byAccount = [];

        foreach ($payment->allocations as $allocation) {
            $allocAmount = round((float) $allocation->amount, 2);
            if ($allocAmount <= 0) {
                continue;
            }

            $votehead = $allocation->invoiceItem?->votehead;
            $accountId = $votehead?->account_id ?? $defaultRevenue->id;
            $byAccount[$accountId] = ($byAccount[$accountId] ?? 0) + $allocAmount;
        }

        $allocatedTotal = round(array_sum($byAccount), 2);
        $unallocated = round($paymentAmount - $allocatedTotal, 2);

        if ($byAccount === []) {
            $byAccount[$defaultRevenue->id] = $paymentAmount;

            return $this->formatCredits($byAccount, $payment);
        }

        if ($unallocated > 0.009) {
            $byAccount[$defaultRevenue->id] = ($byAccount[$defaultRevenue->id] ?? 0) + $unallocated;
        }

        return $this->formatCredits($byAccount, $payment);
    }

  /**
     * @param  array<int, float>  $byAccount
     * @return array<int, array{account_id: int, credit: float, description?: string}>
     */
    protected function formatCredits(array $byAccount, Payment $payment): array
    {
        $lines = [];
        foreach ($byAccount as $accountId => $amount) {
            $lines[] = [
                'account_id' => (int) $accountId,
                'credit' => round($amount, 2),
                'description' => 'Fee income — ' . ($payment->receipt_number ?? $payment->transaction_code),
            ];
        }

        return $lines;
    }
}

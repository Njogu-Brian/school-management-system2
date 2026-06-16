<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerPosting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalPostingService
{
    /**
     * @param  array<int, array{account_id: int, debit?: float, credit?: float, description?: string}>  $lines
     */
    public function post(
        array $lines,
        string $description,
        Carbon|string $entryDate,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?User $user = null,
    ): JournalEntry {
        $normalized = $this->normalizeLines($lines);
        $this->assertBalanced($normalized);

        $date = $entryDate instanceof Carbon ? $entryDate : Carbon::parse($entryDate);

        return DB::transaction(function () use ($normalized, $description, $date, $sourceType, $sourceId, $user) {
            $entry = JournalEntry::create([
                'entry_date' => $date->toDateString(),
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => $user?->id,
                'created_by' => $user?->id,
            ]);

            foreach ($normalized as $index => $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'line_order' => $index + 1,
                ]);

                $this->mirrorToLedgerPosting($entry, $line, $date);
            }

            return $entry->load('lines.account');
        });
    }

    public function systemAccount(string $code): Account
    {
        $account = Account::query()->where('code', $code)->first();
        if (! $account) {
            throw new \RuntimeException("System account [{$code}] is not configured. Run ChartOfAccountsSeeder.");
        }

        return $account;
    }

    /**
     * @param  array<int, array{account_id: int, debit?: float, credit?: float, description?: string}>  $lines
     * @return array<int, array{account_id: int, debit: float, credit: float, description?: string}>
     */
    protected function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException('A journal line cannot have both debit and credit amounts.');
            }

            $normalized[] = [
                'account_id' => (int) $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
            ];
        }

        if ($normalized === []) {
            throw new \InvalidArgumentException('Journal entry must contain at least one line.');
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string}>  $lines
     */
    protected function assertBalanced(array $lines): void
    {
        $debits = round(array_sum(array_column($lines, 'debit')), 2);
        $credits = round(array_sum(array_column($lines, 'credit')), 2);

        if (abs($debits - $credits) > 0.009) {
            throw new \InvalidArgumentException("Journal entry is not balanced (debits {$debits}, credits {$credits}).");
        }
    }

    /**
     * @param  array{account_id: int, debit: float, credit: float, description?: string}  $line
     */
    protected function mirrorToLedgerPosting(JournalEntry $entry, array $line, Carbon $date): void
    {
        $account = Account::findOrFail($line['account_id']);

        if ($line['debit'] > 0) {
            LedgerPosting::create([
                'source_type' => 'journal_entry',
                'source_id' => $entry->id,
                'account_code' => $account->code,
                'dr_cr' => 'dr',
                'amount' => $line['debit'],
                'posting_date' => $date->toDateString(),
            ]);
        }

        if ($line['credit'] > 0) {
            LedgerPosting::create([
                'source_type' => 'journal_entry',
                'source_id' => $entry->id,
                'account_code' => $account->code,
                'dr_cr' => 'cr',
                'amount' => $line['credit'],
                'posting_date' => $date->toDateString(),
            ]);
        }
    }
}

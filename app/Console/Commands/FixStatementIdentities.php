<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Models\ExpenseStatementLine;
use App\Models\MpesaC2BTransaction;
use App\Services\Finance\MpesaStatementIdentity;
use App\Services\Finance\MpesaTransactionClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites statement identity fields (narration whitespace, payee, masked phone)
 * so they match the source M-Pesa statement / Daraja FirstName.
 *
 * SAFETY — never touches payments, amounts, allocations, student matches,
 * expense_id, review_status, or journal entries.
 */
class FixStatementIdentities extends Command
{
    protected $signature = 'finance:fix-statement-identities
        {--apply : Persist the changes (without this flag the command only reports)}
        {--limit=0 : Stop after N row updates (0 = no limit)}';

    protected $description = 'Fix statement payee names, 0700***000 phones, and combined narrations without touching payments.';

    public function __construct(protected MpesaTransactionClassifier $classifier)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit');

        $this->info($apply
            ? 'APPLYING identity fixes (payments / amounts / allocations will NOT change).'
            : 'DRY RUN (no changes saved — pass --apply to commit).');

        $bank = $this->fixBankStatements($apply, $limit);
        $expense = $this->fixExpenseLines($apply, $limit);

        $this->newLine();
        $this->info(sprintf(
            'Bank statements: %d scanned, %d %s. Expense lines: %d scanned, %d %s.',
            $bank['scanned'],
            $bank['changed'],
            $apply ? 'updated' : 'would change',
            $expense['scanned'],
            $expense['changed'],
            $apply ? 'updated' : 'would change'
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to commit.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{scanned: int, changed: int}
     */
    protected function fixBankStatements(bool $apply, int $limit): array
    {
        $scanned = 0;
        $changed = 0;
        $samples = [];

        BankStatementTransaction::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($apply, $limit, &$scanned, &$changed, &$samples) {
                $c2bByRef = MpesaC2BTransaction::query()
                    ->whereIn('trans_id', $rows->pluck('reference_number')->filter()->unique()->all())
                    ->get(['trans_id', 'first_name', 'middle_name', 'last_name'])
                    ->keyBy('trans_id');

                foreach ($rows as $row) {
                    if ($limit > 0 && $changed >= $limit) {
                        return false;
                    }
                    $scanned++;

                    $description = MpesaStatementIdentity::normalizeWhitespace((string) $row->description);
                    $party = MpesaStatementIdentity::parseParty($description);
                    $phone = $party['phone'] ?: MpesaStatementIdentity::extractPhoneFromText($description);

                    $daraja = $c2bByRef->get($row->reference_number);
                    $darajaName = $daraja
                        ? MpesaStatementIdentity::darajaFullName($daraja->first_name, $daraja->middle_name, $daraja->last_name)
                        : null;
                    $payer = MpesaStatementIdentity::preferStatementOrDaraja($party['name'], $darajaName);

                    $dirty = $description !== (string) $row->description
                        || $payer !== $row->payer_name
                        || $phone !== $row->phone_number;

                    if (! $dirty) {
                        continue;
                    }

                    if (count($samples) < 8) {
                        $samples[] = [
                            $row->id,
                            $row->reference_number,
                            mb_strimwidth((string) $row->payer_name, 0, 18, '…'),
                            mb_strimwidth((string) $payer, 0, 22, '…'),
                            (string) $row->phone_number,
                            (string) $phone,
                        ];
                    }

                    if ($apply) {
                        DB::table('bank_statement_transactions')->where('id', $row->id)->update([
                            'description' => $description !== '' ? $description : $row->description,
                            'payer_name' => $payer,
                            'phone_number' => $phone,
                            'updated_at' => now(),
                        ]);
                    }

                    $changed++;
                }

                return true;
            });

        if ($samples !== []) {
            $this->newLine();
            $this->comment('Bank statement samples (old payer / new payer / old phone / new phone):');
            $this->table(['id', 'ref', 'old payer', 'new payer', 'old phone', 'new phone'], $samples);
        }

        return ['scanned' => $scanned, 'changed' => $changed];
    }

    /**
     * @return array{scanned: int, changed: int}
     */
    protected function fixExpenseLines(bool $apply, int $limit): array
    {
        $scanned = 0;
        $changed = 0;
        $samples = [];

        ExpenseStatementLine::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($apply, $limit, &$scanned, &$changed, &$samples) {
                foreach ($rows as $line) {
                    if ($limit > 0 && $changed >= $limit) {
                        return false;
                    }
                    $scanned++;

                    $narration = MpesaStatementIdentity::normalizeWhitespace((string) $line->narration);
                    $classified = $this->classifier->classify(
                        $narration,
                        (float) $line->withdrawn_amount,
                        (float) $line->paid_in_amount,
                    );

                    $updates = [];
                    if ($narration !== (string) $line->narration) {
                        $updates['narration'] = $narration;
                        $updates['line_fingerprint'] = ExpenseStatementLine::fingerprint(
                            $line->receipt_no,
                            $line->completed_at,
                            $narration,
                        );
                    }

                    if ($line->is_transaction_fee || $classified['is_transaction_fee']) {
                        // Fees inherit payee from the parent transfer — do not wipe that.
                        if ($updates === []) {
                            continue;
                        }
                    } else {
                        if ($classified['recipient_name'] !== $line->recipient_name) {
                            $updates['recipient_name'] = $classified['recipient_name'];
                        }
                        if ($classified['recipient_phone'] !== $line->recipient_phone) {
                            $updates['recipient_phone'] = $classified['recipient_phone'];
                        }
                        if ($classified['transaction_type'] !== $line->transaction_type) {
                            $updates['transaction_type'] = $classified['transaction_type'];
                        }
                        if ($classified['merchant_reference'] !== $line->merchant_reference) {
                            $updates['merchant_reference'] = $classified['merchant_reference'];
                        }
                        if ($classified['paybill_number'] !== $line->paybill_number) {
                            $updates['paybill_number'] = $classified['paybill_number'];
                        }
                        if ($classified['account_reference'] !== $line->account_reference) {
                            $updates['account_reference'] = $classified['account_reference'];
                        }

                        // Only regroup unbooked lines so confirmed expenses stay linked as they are.
                        if ($line->expense_id === null && $classified['group_key'] !== $line->group_key) {
                            $updates['group_key'] = $classified['group_key'];
                        }
                    }

                    if ($updates === []) {
                        continue;
                    }

                    if (count($samples) < 8) {
                        $samples[] = [
                            $line->id,
                            $line->receipt_no,
                            $line->transaction_type,
                            $classified['transaction_type'],
                            mb_strimwidth((string) $line->recipient_name, 0, 24, '…'),
                            mb_strimwidth((string) $classified['recipient_name'], 0, 24, '…'),
                        ];
                    }

                    if ($apply) {
                        $updates['updated_at'] = now();
                        DB::table('expense_statement_lines')->where('id', $line->id)->update($updates);
                    }

                    $changed++;
                }

                return true;
            });

        if ($samples !== []) {
            $this->newLine();
            $this->comment('Expense line samples (old type / new type / old payee / new payee):');
            $this->table(['id', 'receipt', 'old type', 'new type', 'old payee', 'new payee'], $samples);
        }

        return ['scanned' => $scanned, 'changed' => $changed];
    }
}

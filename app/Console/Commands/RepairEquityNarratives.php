<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Services\BankStatementParser;
use App\Services\Finance\MpesaStatementIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-read Equity PDFs and rewrite stored narration / payee / phone so they match
 * the statement. Does not touch payments, amounts, allocations, or student matches.
 */
class RepairEquityNarratives extends Command
{
    protected $signature = 'finance:repair-equity-narratives
        {--apply : Persist the changes (without this flag the command only reports)}
        {--statement= : Limit to one statement_file_path}
        {--limit=0 : Stop after N row updates (0 = no limit)}';

    protected $description = 'Restore full Equity statement narratives (and phones) from the original PDF without touching payments.';

    public function handle(BankStatementParser $parser): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit');
        $only = trim((string) $this->option('statement'));

        $this->info($apply
            ? 'APPLYING narrative repairs (payments / amounts / allocations will NOT change).'
            : 'DRY RUN (no changes saved — pass --apply to commit).');

        $paths = BankStatementTransaction::query()
            ->where('bank_type', 'equity')
            ->whereNotNull('statement_file_path')
            ->where('statement_file_path', '!=', '')
            ->when($only !== '', fn ($q) => $q->where('statement_file_path', $only))
            ->distinct()
            ->pluck('statement_file_path');

        if ($paths->isEmpty()) {
            $this->warn('No Equity statement files found.');

            return self::SUCCESS;
        }

        $scannedFiles = 0;
        $missingFiles = 0;
        $updated = 0;
        $samples = [];

        foreach ($paths as $statementPath) {
            if ($limit > 0 && $updated >= $limit) {
                break;
            }

            $fullPath = storage_local_path(config('filesystems.private_disk', 'private'), $statementPath);
            if (! is_file($fullPath)) {
                $missingFiles++;
                $this->warn('PDF missing: '.$statementPath);

                continue;
            }

            $scannedFiles++;
            $parsed = $parser->parseStatementToArray($fullPath, 'equity');
            if ($parsed === []) {
                $this->warn('Parser returned no rows: '.$statementPath);

                continue;
            }

            $existing = BankStatementTransaction::query()
                ->where('bank_type', 'equity')
                ->where('statement_file_path', $statementPath)
                ->where('is_duplicate', false)
                ->get();

            $usedIds = [];

            foreach ($parsed as $row) {
                if ($limit > 0 && $updated >= $limit) {
                    break 2;
                }

                $match = $this->matchExisting($existing, $row, $usedIds);
                if ($match === null) {
                    continue;
                }
                $usedIds[] = $match->id;

                $particulars = MpesaStatementIdentity::normalizeWhitespace((string) ($row['particulars'] ?? ''));
                if ($particulars === '') {
                    continue;
                }

                $party = MpesaStatementIdentity::parseParty($particulars);
                $fromText = $party['phone'] ?: MpesaStatementIdentity::extractPhoneFromText($particulars);
                $fromParser = MpesaStatementIdentity::toLocalMaskedPhone($row['phone_number_extracted'] ?? null);
                $phone = $fromText;
                if ($phone === null && $fromParser && preg_match('/MPESA|PAY BILL|2547|\b07\d/i', $particulars)) {
                    $phone = $fromParser;
                }
                $payer = $party['name'];

                $newDescription = $this->shouldReplaceNarration((string) $match->description, $particulars)
                    ? $particulars
                    : (string) $match->description;

                $dirty = $newDescription !== (string) $match->description
                    || $phone !== $match->phone_number
                    || $payer !== $match->payer_name;

                if (! $dirty) {
                    continue;
                }

                $updated++;
                if (count($samples) < 12) {
                    $samples[] = [
                        $match->id,
                        $match->reference_number,
                        mb_strimwidth((string) $match->description, 0, 36, '…'),
                        mb_strimwidth($newDescription, 0, 48, '…'),
                        (string) $match->phone_number,
                        (string) $phone,
                    ];
                }

                if ($apply) {
                    DB::table('bank_statement_transactions')->where('id', $match->id)->update([
                        'description' => $newDescription,
                        'phone_number' => $phone,
                        'payer_name' => $payer,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if ($samples !== []) {
            $this->table(
                ['id', 'ref', 'old description', 'new description', 'old phone', 'new phone'],
                $samples
            );
        }

        $this->info(sprintf(
            'Files scanned: %d. Missing PDFs: %d. Rows %s: %d.',
            $scannedFiles,
            $missingFiles,
            $apply ? 'updated' : 'would change',
            $updated
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to commit.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BankStatementTransaction>  $existing
     * @param  array<string, mixed>  $row
     * @param  list<int>  $usedIds
     */
    protected function matchExisting($existing, array $row, array $usedIds): ?BankStatementTransaction
    {
        $code = trim((string) ($row['transaction_code'] ?? ''));
        $dateStr = $this->rowDate($row);
        $credit = (float) ($row['credit'] ?? 0);
        $debit = (float) ($row['debit'] ?? 0);
        $amount = $credit > 0 ? $credit : $debit;
        $type = $credit > 0 ? 'credit' : 'debit';

        if ($dateStr === '' || $amount <= 0) {
            return null;
        }

        foreach ($existing as $txn) {
            if (in_array($txn->id, $usedIds, true)) {
                continue;
            }
            $txnDate = $txn->transaction_date ? $txn->transaction_date->format('Y-m-d') : '';
            if ($txnDate !== $dateStr) {
                continue;
            }
            if (abs((float) $txn->amount - $amount) > 0.01) {
                continue;
            }
            $txnType = $txn->transaction_type ?: 'credit';
            if ($txnType !== $type) {
                continue;
            }
            $currentRef = trim((string) ($txn->reference_number ?? ''));
            if ($code !== '' && $currentRef !== '' && strcasecmp($currentRef, $code) !== 0) {
                continue;
            }

            return $txn;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function rowDate(array $row): string
    {
        $tranDate = $row['tran_date'] ?? null;
        if ($tranDate instanceof \DateTimeInterface) {
            return $tranDate->format('Y-m-d');
        }
        if (is_string($tranDate) && $tranDate !== '') {
            return substr($tranDate, 0, 10);
        }

        return '';
    }

    protected function shouldReplaceNarration(string $old, string $new): bool
    {
        $oldN = MpesaStatementIdentity::normalizeWhitespace($old);
        $newN = MpesaStatementIdentity::normalizeWhitespace($new);
        if ($newN === '' || $newN === $oldN) {
            return false;
        }
        if (preg_match_all('/BY\s*:/i', $newN) > 1) {
            return false;
        }
        if (preg_match('/BY\s*:/i', $newN) && preg_match('/WAIRI\s*\//i', $newN)) {
            return false;
        }
        if (preg_match('/BY\s*:/i', $newN) && preg_match('/APP\s*\//i', $newN)) {
            return false;
        }
        if (preg_match_all('/\b\d{2}\/\d{2}\/\d{4}\b/', $newN) >= 2) {
            return false;
        }
        $oldU = strtoupper($oldN);
        $newU = strtoupper($newN);
        if (! str_contains($newU, $oldU) && ! str_contains($oldU, $newU)) {
            return false;
        }
        if (strlen($newN) < strlen($oldN) && str_contains($oldU, $newU)) {
            return false;
        }

        return true;
    }
}

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
        {--from= : Only rows with transaction_date on/after YYYY-MM-DD}
        {--to= : Only rows with transaction_date on/before YYYY-MM-DD}
        {--limit=0 : Stop after N row updates (0 = no limit)}';

    protected $description = 'Restore full Equity statement narratives (and phones) from the original PDF without touching payments.';

    public function handle(BankStatementParser $parser): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit');
        $only = trim((string) $this->option('statement'));
        $from = trim((string) $this->option('from'));
        $to = trim((string) $this->option('to'));

        $this->info($apply
            ? 'APPLYING narrative repairs (payments / amounts / allocations will NOT change).'
            : 'DRY RUN (no changes saved — pass --apply to commit).');
        if ($from !== '' || $to !== '') {
            $this->line(sprintf('Date window: %s → %s', $from !== '' ? $from : '…', $to !== '' ? $to : '…'));
        }

        $paths = BankStatementTransaction::query()
            ->where('bank_type', 'equity')
            ->whereNotNull('statement_file_path')
            ->where('statement_file_path', '!=', '')
            ->when($only !== '', fn ($q) => $q->where('statement_file_path', $only))
            ->when($from !== '', fn ($q) => $q->whereDate('transaction_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('transaction_date', '<=', $to))
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
                ->when($from !== '', fn ($q) => $q->whereDate('transaction_date', '>=', $from))
                ->when($to !== '', fn ($q) => $q->whereDate('transaction_date', '<=', $to))
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

                $parsedCode = trim((string) ($row['transaction_code'] ?? ''));
                $currentRef = trim((string) ($match->reference_number ?? ''));
                $exactRefMatch = $parsedCode !== '' && $currentRef !== '' && strcasecmp($parsedCode, $currentRef) === 0;

                $newDescription = $this->shouldReplaceNarration((string) $match->description, $particulars, $exactRefMatch)
                    ? $particulars
                    : (string) $match->description;

                $dirty = $newDescription !== (string) $match->description
                    || ($phone !== null && $phone !== $match->phone_number)
                    || ($payer !== null && $payer !== '' && $payer !== $match->payer_name);

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
                    $payload = [
                        'description' => $newDescription,
                        'updated_at' => now(),
                    ];
                    // Narration-only repair: never blank out an existing phone / payer.
                    if ($phone !== null && $phone !== '') {
                        $payload['phone_number'] = $phone;
                    }
                    if ($payer !== null && $payer !== '') {
                        $payload['payer_name'] = $payer;
                    }
                    DB::table('bank_statement_transactions')->where('id', $match->id)->update($payload);
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

    protected function shouldReplaceNarration(string $old, string $new, bool $exactRefMatch = false): bool
    {
        $oldN = MpesaStatementIdentity::normalizeWhitespace($old);
        $newN = MpesaStatementIdentity::normalizeWhitespace($new);
        if ($newN === '' || strcasecmp($newN, $oldN) === 0) {
            return false;
        }

        if ($this->looksMergedNarration($newN)) {
            return false;
        }

        $oldU = strtoupper($oldN);
        $newU = strtoupper($newN);
        $oldComplete = $this->looksCompleteEquityNarration($oldN);
        $newComplete = $this->looksCompleteEquityNarration($newN);

        // Never regress a clean APP/... / EAZZY... line into a wrap fragment or loan-recovery mash.
        if ($oldComplete && ! $newComplete) {
            return false;
        }
        if (str_starts_with($oldU, 'APP/') && ! str_contains($newU, 'APP/')) {
            return false;
        }
        if (str_starts_with($oldU, 'EAZZY-') && ! str_contains($newU, 'EAZZY-')) {
            return false;
        }
        if (str_starts_with($oldU, 'CHICKEN') && ! str_starts_with($newU, 'CHICKEN')) {
            return false;
        }
        if (! str_contains($oldU, 'CHICKEN') && str_contains($newU, 'CHICKEN')) {
            return false;
        }
        // Don't strip a leading payer/merchant name down to a bare BY: continuation.
        if (preg_match('/BY\s*:/i', $oldN) && preg_match('/^BY\s*:/i', $newN) && ! preg_match('/^BY\s*:/i', $oldN)) {
            return false;
        }
        // Table remarks often inject hex crumbs into an already-readable APP/MPESA / USSD name.
        // e.g. "PURITY MWARI" -> "PURITY B37 7 MWAR"
        if (preg_match('/^(APP\/MPESA|USSD\/MPESA|APP\/)/i', $oldN)
            && preg_match('/\s[0-9A-F]{3,4}\s+[0-9A-Z]{1,2}\s/i', $newN)
            && ! preg_match('/\s[0-9A-F]{3,4}\s+[0-9A-Z]{1,2}\s/i', $oldN)) {
            return false;
        }
        // Remarks bleed (RENT / PAINTING / account digits) into an already-good APP/MPESA name.
        if (preg_match('/^(APP\/MPESA|USSD\/MPESA)/i', $oldN)
            && preg_match('/\b(RENT|PAINTING)\b/i', $newN)
            && ! preg_match('/\b(RENT|PAINTING)\b/i', $oldN)) {
            return false;
        }
        if (str_starts_with($oldU, 'CHICKEN')
            && str_starts_with($newU, 'CHICKEN')
            && preg_match('/\b0120263\d+\b/', $newN)
            && ! preg_match('/\b0120263\d+\b/', $oldN)) {
            return false;
        }
        if (str_contains($newU, '627851XXXXXX') && ! str_contains($oldU, '627851')) {
            return false;
        }
        if (preg_match('/^LOAN (RECOVERY|PAYMENT)/i', $oldN) && ! preg_match('/LOAN (RECOVERY|PAYMENT)/i', $newN)) {
            return false;
        }
        if (preg_match('/^LOAN (RECOVERY|PAYMENT)/i', $oldN) && preg_match('/^LOAN (RECOVERY|PAYMENT)/i', $newN)
            && strlen($newN) <= strlen($oldN) + 5) {
            // Prefer the cleaner existing loan line over account-number-prefixed table noise.
            return false;
        }
        if (! preg_match('/LOAN (RECOVERY|PAYMENT)/i', $oldN) && preg_match('/LOAN (RECOVERY|PAYMENT)/i', $newN) && $oldComplete) {
            return false;
        }

        // Don't pollute an already-clean charge / APP line with wrap crumbs or hex leftovers.
        if (preg_match('/^(SMS\s*CHARGE|TRANSACTION\s*\+\s*SMS\s*CHARGE)$/i', $oldN)
            && ! preg_match('/^(SMS\s*CHARGE|TRANSACTION\s*\+\s*SMS\s*CHARGE)$/i', $newN)) {
            return false;
        }
        if ($oldComplete && $newComplete && str_starts_with($newU, $oldU) && strlen($newN) > strlen($oldN) + 2) {
            return false;
        }
        if (preg_match('/^MPS\s/i', $newN) && preg_match_all('/\bMPS\b/i', $newN) > 1) {
            return false;
        }
        if (preg_match_all('/\b2547\d{8}\b/', $newN) > 1 && preg_match_all('/\b2547\d{8}\b/', $oldN) <= 1) {
            return false;
        }

        // Opaque OCR/scrap tokens (e.g. yalxNkpD4Mim, TAKpa2xvPiqM) should not pollute a clean narration.
        if (
            (
                preg_match('/\b[a-z]{2,}[A-Z0-9][a-zA-Z0-9]*\b/', $newN)
                || preg_match('/\b(?=[A-Za-z0-9]*[a-z])(?=[A-Za-z0-9]*[A-Z])(?=[A-Za-z0-9]*\d)[A-Za-z0-9]{6,}\b/', $newN)
            )
            && ! preg_match('/\b[a-z]{2,}[A-Z0-9][a-zA-Z0-9]*\b/', $oldN)
            && ! preg_match('/\b(?=[A-Za-z0-9]*[a-z])(?=[A-Za-z0-9]*[A-Z])(?=[A-Za-z0-9]*\d)[A-Za-z0-9]{6,}\b/', $oldN)
        ) {
            return false;
        }

        $oldTokens = $this->narrativeCoreTokens($oldN);
        $newTokens = $this->narrativeCoreTokens($newN);
        $shared = array_intersect($oldTokens, $newTokens);

        // Never drop payer/person names from MPS / EAZZY narrations.
        if (preg_match('/^MPS\s/i', $oldN) && preg_match('/^MPS\s/i', $newN)) {
            $missingNames = array_values(array_filter(
                $oldTokens,
                fn (string $token) => strlen($token) >= 4
                    && ! in_array($token, $newTokens, true)
                    && ! preg_match('/^(MPS|EQA|TPG|SMS)$/', $token)
                    && ! preg_match('/^\d+$/', $token)
                    && ! preg_match('/^[A-Z0-9]{8,}$/', $token)
            ));
            if ($missingNames !== []) {
                return false;
            }
        }
        if (preg_match('/^EAZZY-/i', $oldN) && preg_match('/^EAZZY-/i', $newN)) {
            $missingNames = array_values(array_filter(
                $oldTokens,
                fn (string $token) => strlen($token) >= 4
                    && ! in_array($token, $newTokens, true)
                    && ! preg_match('/^(EAZZY|FUNDS|TRNSF|FRM|TPG)$/', $token)
                    && ! preg_match('/^\d+$/', $token)
            ));
            if ($missingNames !== []) {
                return false;
            }
        }

        // Never shrink a good narration down to a wrap fragment.
        if (strlen($newN) < strlen($oldN) && str_contains($oldU, $newU)) {
            return false;
        }
        if ($oldComplete && strlen($newN) + 8 < strlen($oldN)) {
            return false;
        }

        // Exact containment either way (fuller PDF text vs truncated DB text).
        if (str_contains($newU, $oldU) || str_contains($oldU, $newU)) {
            // Prefer the longer / more complete side.
            if (strlen($newN) >= strlen($oldN) || ($newComplete && ! $oldComplete)) {
                return true;
            }

            return false;
        }

        // Truncated crumbs like "KINUTHIA/ 8 8" vs "APP/JAMES NDUNGU KINUTHIA/".
        $oldLooksFragment = (strlen($oldN) <= 28 && ! $oldComplete)
            || (bool) preg_match('/^[A-Z][A-Z\'\-]+\/\s*[0-9A-Z ]{0,12}$/i', $oldN)
            || (bool) preg_match('/^[A-Z][A-Z\'\-]+\/\s+[0-9A-Z]{1,4}\s+[0-9A-Z]{1,4}\b/i', $oldN);

        // Don't scramble a readable fee/name narration into table scrap.
        if (! $oldLooksFragment && ! $newComplete && strlen($oldN) >= 20) {
            return false;
        }
        if (preg_match('/\bADM\s*\d+/i', $oldN) && ! $newComplete && ! str_contains($newU, $oldU)) {
            return false;
        }

        if ($newComplete && $oldLooksFragment && ($shared !== [] || strlen($oldN) <= 28)) {
            return true;
        }

        if (strlen($newN) > strlen($oldN) + 3 && $shared !== [] && ($newComplete || ! $oldComplete)) {
            return true;
        }

        // Exact ref match still must be an improvement, not a blind overwrite.
        if ($exactRefMatch && $newComplete && strlen($newN) >= strlen($oldN)) {
            return $shared !== [] || $oldLooksFragment;
        }

        return false;
    }

    protected function looksMergedNarration(string $text): bool
    {
        if (preg_match_all('/BY\s*:/i', $text) > 1) {
            return true;
        }
        if (preg_match('/BY\s*:/i', $text) && preg_match('/WAIRI\s*\//i', $text)) {
            return true;
        }
        if (preg_match('/BY\s*:/i', $text) && preg_match('/APP\s*\//i', $text)) {
            return true;
        }
        if (preg_match_all('/\b\d{2}\/\d{2}\/\d{4}\b/', $text) >= 2 && ! preg_match('/BY\s*:/i', $text)) {
            return true;
        }
        // Loan recovery glued onto another transaction.
        if (preg_match('/LOAN RECOVERY/i', $text) && preg_match('/(APP\/|CHICKEN|EAZZY-|USSD\/|MPS\s)/i', $text)) {
            return true;
        }
        // Charge line that still carries the next merchant narrative.
        if (preg_match('/\bCHARGE\b/i', $text) && preg_match('/(CHICKEN|APP\/|EAZZY-)/i', $text)) {
            return true;
        }

        return false;
    }

    protected function looksCompleteEquityNarration(string $text): bool
    {
        return (bool) preg_match(
            '/^(APP\/|MPS\s|BY:|CHICKEN|USSD\/|PESALINK|EAZZY|TRANSACTION(\s*\+\s*SMS)?\s*CHARGE|SMS\s*CHARGE|LOAN\s+(RECOVERY|PAYMENT)|PATRICK|TPG\s)/i',
            $text
        );
    }

    /**
     * @return list<string>
     */
    protected function narrativeCoreTokens(string $text): array
    {
        $stop = [
            'APP', 'MPS', 'BY', 'USSD', 'EQA', 'TPG', 'CHARGE', 'SMS', 'TRANSACTION',
            'FROM', 'THE', 'AND', 'FOR', 'VIA', 'INC', 'PAY', 'BILL',
        ];
        preg_match_all('/[A-Za-z]{3,}/', strtoupper($text), $m);
        $tokens = [];
        foreach ($m[0] as $token) {
            if (! in_array($token, $stop, true)) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }
}

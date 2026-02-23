<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Services\BankStatementParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixEquityRefsFromReparse extends Command
{
    protected $signature = 'finance:fix-equity-refs-from-reparse
                            {--statement= : Statement file path (as in DB statement_file_path)}
                            {--file= : Use this local PDF path when stored file is missing (e.g. after DB import from production)}
                            {--list : List Equity statement paths from DB (then use one with --statement=)}
                            {--list-invalid : List all Equity transactions with empty or name-like reference (to fix via reparse)}
                            {--dry-run : Show what would be updated without changing data}
                            {--all : Update all matched rows; default is only rows with empty reference_number}';

    protected $description = 'Fix reference_number for Equity transactions by re-parsing the PDF. By default updates empty or invalid refs (e.g. payer names like KINUTHIA); keeps valid refs (numeric, S+digits, MPS codes). Use --all to overwrite all.';

    public function handle(): int
    {
        $statementPath = $this->option('statement');
        $localFile = $this->option('file');
        $dryRun = $this->option('dry-run');
        $list = $this->option('list');
        $listInvalid = $this->option('list-invalid');
        $updateAll = $this->option('all');

        if ($listInvalid) {
            return $this->listInvalidRefs();
        }

        if ($list) {
            $paths = BankStatementTransaction::where('bank_type', 'equity')
                ->whereNotNull('statement_file_path')
                ->where('statement_file_path', '!=', '')
                ->distinct()
                ->pluck('statement_file_path');
            if ($paths->isEmpty()) {
                $this->info('No Equity statement paths found in DB.');
                return 0;
            }
            $this->line('Equity statement_file_path values (use with --statement=<path>):');
            foreach ($paths as $p) {
                $this->line('  ' . $p);
            }
            $this->line('');
            $this->line('If the PDF is not in storage (e.g. after DB import), use --file=<path> to your local PDF.');
            return 0;
        }

        if ($statementPath === null || trim($statementPath) === '') {
            $this->error('Provide --statement=<path> (the statement_file_path from your DB).');
            $this->line('Run with --list to see available paths.');
            return 1;
        }

        $statementPath = trim($statementPath);
        $parser = new BankStatementParser();

        $pdfPathToParse = $statementPath;
        if ($localFile !== null && trim($localFile) !== '') {
            $localFile = trim($localFile);
            if (!is_file($localFile)) {
                $this->error('File not found: ' . $localFile);
                return 1;
            }
            $pdfPathToParse = $localFile;
            $this->line('Using local PDF: ' . $localFile);
        } else {
            $fullPath = storage_local_path(config('filesystems.private_disk', 'private'), $statementPath);
            if (!file_exists($fullPath)) {
                $this->error('Statement file not found in storage: ' . $fullPath);
                $this->line('If you imported the DB from production, provide the PDF with: --file="C:\path\to\your\statement.pdf"');
                $this->line('Run with --list to see statement paths.');
                return 1;
            }
        }

        $parsed = $parser->parseStatementToArray($pdfPathToParse, 'equity');
        if (empty($parsed)) {
            $this->error('Parser returned no transactions. Check the PDF and path.');
            return 1;
        }

        $this->line('Parsed ' . count($parsed) . ' rows from statement. Matching to existing transactions…');
        if (!$updateAll) {
            $this->line('Updating rows with empty or invalid reference (e.g. KINUTHIA); keeping valid refs. Use --all to overwrite all.');
        }
        $existing = BankStatementTransaction::where('bank_type', 'equity')
            ->where('statement_file_path', $statementPath)
            ->where('transaction_type', 'credit')
            ->get()
            ->keyBy('id');

        $updated = 0;
        $matchedIds = [];

        foreach ($parsed as $row) {
            $tranDate = $row['tran_date'] ?? null;
            $particulars = trim((string) ($row['particulars'] ?? ''));
            $credit = isset($row['credit']) ? (float) $row['credit'] : 0;
            $code = isset($row['transaction_code']) ? trim((string) $row['transaction_code']) : null;
            if ($code === '' || $code === null) {
                continue;
            }
            if ($credit <= 0) {
                continue;
            }

            $dateStr = $tranDate instanceof \DateTimeInterface
                ? $tranDate->format('Y-m-d')
                : (is_string($tranDate) ? substr($tranDate, 0, 10) : null);
            if ($dateStr === null || $dateStr === '') {
                continue;
            }

            foreach ($existing as $txn) {
                if (in_array($txn->id, $matchedIds, true)) {
                    continue;
                }
                $txnDate = $txn->transaction_date ? $txn->transaction_date->format('Y-m-d') : null;
                if ($txnDate !== $dateStr) {
                    continue;
                }
                if (abs((float) $txn->amount - $credit) > 0.01) {
                    continue;
                }
                $desc = trim((string) ($txn->description ?? ''));
                if ($desc !== $particulars && $this->normalizedMatch($desc, $particulars) === false) {
                    continue;
                }

                $current = trim((string) ($txn->reference_number ?? ''));
                if ($current === $code) {
                    $matchedIds[] = $txn->id;
                    continue;
                }
                // By default: update when empty or when current ref looks wrong (e.g. payer name like KINUTHIA, not a real code)
                if (!$updateAll && $current !== '' && $this->looksValidReference($current)) {
                    $matchedIds[] = $txn->id;
                    continue;
                }

                $this->line(sprintf(
                    'Transaction #%d | Amount %s | Date %s | Ref: %s -> %s',
                    $txn->id,
                    $txn->amount,
                    $txnDate,
                    $current === '' ? '(empty)' : $txn->reference_number,
                    $code
                ));
                if (!$dryRun) {
                    $txn->update(['reference_number' => $code]);
                    $updated++;
                } else {
                    $this->warn('  [Dry run] Would set reference_number to: ' . $code);
                    $updated++;
                }
                $matchedIds[] = $txn->id;
                break;
            }
        }

        $this->line('');
        if ($updated > 0) {
            $this->info(($dryRun ? 'Would update ' : 'Updated ') . $updated . ' transaction(s) from re-parse.');
            if ($dryRun) {
                $this->warn('Run without --dry-run to apply changes.');
            }
        } else {
            $this->info('No reference updates needed (or no matches).');
        }

        return 0;
    }

    private function normalizedMatch(string $a, string $b): bool
    {
        $a = preg_replace('/\s+/', ' ', trim($a));
        $b = preg_replace('/\s+/', ' ', trim($b));
        if ($a === $b) {
            return true;
        }
        if (strlen($a) > 20 && strlen($b) > 20 && (str_contains($a, $b) || str_contains($b, $a))) {
            return true;
        }
        return false;
    }

    /**
     * True if the string looks like a real transaction reference (numeric, S+digits, or MPS-style code).
     * False for payer names / words from narration (e.g. KINUTHIA, WAMUTITU, INVESTMENTS) so reparse will replace them.
     */
    private function looksValidReference(string $ref): bool
    {
        $ref = trim($ref);
        if ($ref === '') {
            return false;
        }
        if (preg_match('/\d/', $ref)) {
            return true;
        }
        if (preg_match('/^S\d{6,12}$/i', $ref)) {
            return true;
        }
        return false;
    }

    /**
     * List all Equity transactions with empty or name-like reference (will be fixed when reparse is run).
     */
    private function listInvalidRefs(): int
    {
        $query = BankStatementTransaction::where('bank_type', 'equity')
            ->where('transaction_type', 'credit')
            ->orderBy('transaction_date')
            ->orderBy('id');

        $rows = $query->get();
        $invalid = [];
        foreach ($rows as $txn) {
            $ref = trim((string) ($txn->reference_number ?? ''));
            if ($ref === '' || !$this->looksValidReference($ref)) {
                $invalid[] = $txn;
            }
        }

        if (empty($invalid)) {
            $this->info('No Equity transactions with empty or name-like reference found.');
            return 0;
        }

        $this->line('Equity transactions with empty or name-like reference (run reparse per statement to fix):');
        $this->line('');
        foreach ($invalid as $txn) {
            $desc = $txn->description ?? '';
            if (strlen($desc) > 60) {
                $desc = substr($desc, 0, 57) . '...';
            }
            $this->line(sprintf(
                '  #%d | %s | %s | Ref: %s | %s',
                $txn->id,
                $txn->transaction_date?->format('Y-m-d') ?? '',
                number_format((float) $txn->amount, 2),
                $txn->reference_number === null || $txn->reference_number === '' ? '(empty)' : $txn->reference_number,
                $desc
            ));
        }
        $this->line('');
        $this->info(count($invalid) . ' transaction(s). Fix by running: php artisan finance:fix-equity-refs-from-reparse --statement=<path> [--file=<pdf>]');
        return 0;
    }
}

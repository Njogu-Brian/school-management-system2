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
                            {--dry-run : Show what would be updated without changing data}';

    protected $description = 'Fix reference_number for ALL Equity transaction types (incl. APP) by re-parsing the statement PDF. Uses Transaction Reference column from the PDF. Run for each statement file that has wrong/missing refs.';

    public function handle(): int
    {
        $statementPath = $this->option('statement');
        $localFile = $this->option('file');
        $dryRun = $this->option('dry-run');
        $list = $this->option('list');

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
            $fullPath = Storage::disk('private')->path($statementPath);
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

                $current = $txn->reference_number ?? '';
                if (trim((string) $current) === $code) {
                    $matchedIds[] = $txn->id;
                    continue;
                }

                $this->line(sprintf(
                    'Transaction #%d | Amount %s | Date %s | Ref: %s -> %s',
                    $txn->id,
                    $txn->amount,
                    $txnDate,
                    $current === '' ? '(empty)' : $current,
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
}

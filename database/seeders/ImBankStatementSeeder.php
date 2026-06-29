<?php

namespace Database\Seeders;

use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads an I&M Bank PDF statement (withdrawals only) into the expense statement
 * analyzer as a new "bank" import, so the transactions can be classified and
 * approved like any M-Pesa statement.
 *
 * The transactions are pre-built by scripts/build_imbank_seeder.py, which:
 *   - parses the I&M PDF (Tran Date / Withdrawals / Narrative columns), and
 *   - matches each "MPESA Payment to <phone>" withdrawal to the corresponding
 *     I&M / M-PESA SMS to recover the M-PESA reference + recipient name, and
 *   - keeps any user-typed note (e.g. "254../van welding") as the description.
 *
 * Everything lands as PENDING — you keep full power to edit, categorise and
 * approve. Re-running is safe: the import is created once, and lines are
 * de-duplicated globally by their reference fingerprint.
 *
 * Run with:  php artisan db:seed --class=ImBankStatementSeeder
 */
class ImBankStatementSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/imbank_transactions.json');

        if (! is_file($path)) {
            $this->command?->error("Data file not found: {$path}");
            return;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload) || empty($payload['lines'])) {
            $this->command?->error('Statement data file is empty or invalid.');
            return;
        }

        $uploaderId = User::query()->orderBy('id')->value('id');
        if (! $uploaderId) {
            $this->command?->error('No users found to attribute the import to.');
            return;
        }

        $filename = $payload['original_filename'] ?? 'imbank-statement.pdf';

        // Idempotency: never create the same statement twice.
        $existing = ExpenseStatementImport::query()
            ->where('source', ExpenseStatementImport::SOURCE_BANK)
            ->where('original_filename', $filename)
            ->first();

        if ($existing) {
            $this->command?->warn("I&M statement '{$filename}' already imported (id {$existing->id}); skipping.");
            return;
        }

        DB::transaction(function () use ($payload, $uploaderId, $filename) {
            $import = ExpenseStatementImport::create([
                'uploaded_by' => $uploaderId,
                'source' => ExpenseStatementImport::SOURCE_BANK,
                'original_filename' => $filename,
                'file_path' => 'seeded/imbank/' . $filename,
                'period_start' => $payload['period_start'] ?? null,
                'period_end' => $payload['period_end'] ?? null,
                'account_name' => $payload['account_name'] ?? null,
                'account_number' => $payload['account_number'] ?? null,
                'status' => ExpenseStatementImport::STATUS_PARSED,
                'summary' => [
                    'bank' => $payload['bank'] ?? 'I&M Bank',
                    'seeded_at' => now()->toDateTimeString(),
                    'source_script' => 'scripts/build_imbank_seeder.py',
                ],
            ]);

            // Skip anything whose reference fingerprint already exists anywhere.
            $allFps = array_values(array_unique(array_column($payload['lines'], 'line_fingerprint')));
            $existingFps = [];
            foreach (array_chunk($allFps, 1000) as $chunk) {
                foreach (ExpenseStatementLine::whereIn('line_fingerprint', $chunk)->pluck('line_fingerprint') as $fp) {
                    $existingFps[$fp] = true;
                }
            }

            $now = now();
            $rows = [];
            $lineCount = 0;
            $outgoingCount = 0;
            $outgoingTotal = 0.0;
            $duplicates = 0;
            $seen = [];

            foreach ($payload['lines'] as $line) {
                $fp = $line['line_fingerprint'];
                if (isset($existingFps[$fp]) || isset($seen[$fp])) {
                    $duplicates++;
                    continue;
                }
                $seen[$fp] = true;

                $withdrawn = round((float) ($line['withdrawn_amount'] ?? 0), 2);
                $vendor = $this->clean($line['vendor_name'] ?? null);

                $rows[] = [
                    'import_id' => $import->id,
                    'receipt_no' => $this->clean($line['receipt_no'] ?? null, 32),
                    'completed_at' => $line['completed_at'] ?? null,
                    'narration' => (string) ($line['narration'] ?? ''),
                    'line_fingerprint' => $fp,
                    'withdrawn_amount' => $withdrawn,
                    'paid_in_amount' => 0,
                    'direction' => 'out',
                    'transaction_type' => $line['transaction_type'] ?? 'other',
                    'is_transaction_fee' => ! empty($line['is_transaction_fee']),
                    'recipient_name' => $this->clean($line['recipient_name'] ?? null, 255),
                    'vendor_name' => $vendor,
                    'recipient_phone' => $this->clean($line['recipient_phone'] ?? null, 32),
                    'paybill_number' => null,
                    'account_reference' => null,
                    'merchant_reference' => null,
                    'group_key' => substr((string) ($line['group_key'] ?? sha1($line['narration'] ?? '')), 0, 64),
                    'review_status' => ExpenseStatementLine::REVIEW_PENDING,
                    'expense_category_id' => null,
                    'expense_description' => $this->clean($line['expense_description'] ?? null),
                    'expense_id' => null,
                    'raw_data' => json_encode([
                        'bank' => $payload['bank'] ?? 'I&M Bank',
                        'tran_date' => $line['tran_date'] ?? null,
                        'balance' => $line['balance'] ?? null,
                        'source' => 'imbank_statement_seeder',
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $lineCount++;
                $outgoingCount++;
                $outgoingTotal += $withdrawn;
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('expense_statement_lines')->insert($chunk);
            }

            $import->update([
                'line_count' => $lineCount,
                'outgoing_count' => $outgoingCount,
                'outgoing_total' => round($outgoingTotal, 2),
            ]);

            $this->command?->info("I&M statement imported (id {$import->id}): {$lineCount} lines, "
                . 'KES ' . number_format($outgoingTotal, 2) . " withdrawn, {$duplicates} duplicate(s) skipped.");

            // Auto-categorise & group from previously classified recipients.
            $applied = app(\App\Services\Finance\RecipientMemoryService::class)->applyToPendingLines($import->id);
            $this->command?->info("Auto-categorised {$applied['confirmed']} business + {$applied['personal']} personal "
                . "(phone {$applied['by_phone']}, name {$applied['by_name']}, keyword {$applied['by_keyword']}).");
        });
    }

    private function clean(?string $value, ?int $max = null): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return $max ? mb_substr($value, 0, $max) : $value;
    }
}

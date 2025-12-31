<?php

namespace App\Services;

use App\Models\LegacyFinanceImportBatch;
use App\Models\LegacyStatementLine;
use App\Models\LegacyStatementTerm;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Services\LegacyPdfParser;

class LegacyFinanceImportService
{
    public function __construct(private readonly LegacyPdfParser $parser)
    {
    }

    /**
     * Parse and persist legacy finance statements from a PDF.
     */
    public function import(string $pdfPath, ?string $classLabel = null, ?int $uploadedBy = null, ?LegacyFinanceImportBatch $existingBatch = null): array
    {
        $batch = $existingBatch ?: LegacyFinanceImportBatch::create([
            'uploaded_by' => $uploadedBy,
            'file_name' => basename($pdfPath),
            'class_label' => $classLabel,
        ]);

        $batch->update([
            'status' => 'running',
            'total_students' => 0,
            'imported_students' => 0,
            'draft_students' => 0,
            'file_name' => basename($pdfPath),
            'class_label' => $classLabel ?? $batch->class_label,
        ]);

        $lines = $this->prepareLines($pdfPath, $batch);
        if (empty($lines)) {
            return [
                'batch_id' => $batch->id,
                'file' => $batch->file_name,
                'students_total' => 0,
                'terms_imported' => 0,
                'terms_draft' => 0,
            ];
        }

        $currentStudent = null;
        $currentTerm = null;
        $sequence = 1;
        $studentsSeen = [];

        foreach ($lines as $line) {
            // Detect student header
            if ($student = $this->matchStudentHeader($line)) {
                $currentStudent = $student;
                $currentTerm = null;
                $sequence = 1;
                $studentsSeen[$student['admission_number']] = true;
                continue;
            }

            // Term header
            if ($termHeader = $this->matchTermHeader($line)) {
                $currentTerm = $this->storeTerm($batch, $currentStudent, $termHeader, $classLabel);
                $sequence = 1;
                continue;
            }

            if (!$currentStudent || !$currentTerm) {
                continue; // ignore content until both are known
            }

            // Current balance closes the term
            if ($this->isCurrentBalanceLine($line)) {
                $amount = $this->extractAmountFromBalanceLine($line);
                if ($amount !== null) {
                    $currentTerm->ending_balance = $amount;
                    $currentTerm->save();
                }
                continue;
            }

            // Fees summary line can also set ending balance
            if (stripos($line, 'Fees as at statement date') !== false) {
                $amount = $this->extractTrailingAmount($line);
                if ($amount !== null) {
                    $currentTerm->ending_balance = $amount;
                    $currentTerm->save();
                }
                continue;
            }

            // Transaction rows (date first)
            if ($txn = $this->matchTransactionLine($line)) {
                $lineModel = $this->storeTransaction($batch, $currentTerm, $txn, $sequence);
                $sequence++;

                if ($lineModel->txn_type === 'balance_bf' && $currentTerm->starting_balance === null) {
                    $currentTerm->starting_balance = $lineModel->running_balance ?? ($lineModel->amount_dr ?? $lineModel->amount_cr);
                    $currentTerm->save();
                }

                if ($lineModel->confidence === 'draft') {
                    $currentTerm->confidence = 'draft';
                    $currentTerm->status = 'draft';
                    $currentTerm->save();
                }

                // Update ending_balance from the last transaction's running balance if not explicitly set
                if ($lineModel->running_balance !== null && $currentTerm->ending_balance === null) {
                    $currentTerm->ending_balance = $lineModel->running_balance;
                    $currentTerm->save();
                }
            }
        }

        // Finalize ending balances for all terms in the batch
        // For terms that don't have an ending_balance set, use the running_balance from the last line
        $this->finalizeTermEndingBalances($batch->id);

        $imported = LegacyStatementTerm::where('batch_id', $batch->id)->where('status', 'imported')->count();
        $draft = LegacyStatementTerm::where('batch_id', $batch->id)->where('status', 'draft')->count();

        $batch->update([
            'status' => 'pending_review',
            'total_students' => count($studentsSeen),
            'imported_students' => $imported,
            'draft_students' => $draft,
        ]);

        return [
            'batch_id' => $batch->id,
            'file' => $batch->file_name,
            'students_total' => count($studentsSeen),
            'terms_imported' => $imported,
            'terms_draft' => $draft,
        ];
    }

    private function prepareLines(string $pdfPath, LegacyFinanceImportBatch $batch): array
    {
        $lines = $this->parser->getLines($pdfPath);
        if (empty($lines)) {
            \Log::error('Parser returned no lines', ['pdf' => $pdfPath, 'batch_id' => $batch->id]);
            $batch->update(['status' => 'failed']);
        }
        return array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
    }

    private function matchStudentHeader(string $line): ?array
    {
        if (preg_match('/^Student:\s*(.+?)\s*\(([^)]+)\)/i', $line, $m)) {
            return [
                'name' => trim($m[1]),
                'admission_number' => trim($m[2]),
            ];
        }

        return null;
    }

    private function matchTermHeader(string $line): ?array
    {
        if (preg_match('/^(?<year>\d{4})\s*\/\s*(?<start>[A-Z]{3})\s*-\s*(?<end>[A-Z]{3})\s*-\s*(?<class>GRADE\s+\d+)/i', $line, $m)) {
            $termName = strtoupper(trim($m['start'] . '-' . $m['end']));

            return [
                'academic_year' => (int) $m['year'],
                'term_name' => $termName,
                'term_number' => $this->mapTermNumber($termName),
                'class_label' => strtoupper(trim($m['class'])),
                'source_label' => trim($line),
            ];
        }

        return null;
    }

    private function mapTermNumber(string $termName): ?int
    {
        return match (strtoupper($termName)) {
            'JAN-APR' => 1,
            'MAY-AUG' => 2,
            'SEP-DEC' => 3,
            default => null,
        };
    }

    private function storeTerm(LegacyFinanceImportBatch $batch, array $student, array $termHeader, ?string $classLabel): LegacyStatementTerm
    {
        $studentModel = Student::where('admission_number', $student['admission_number'])->first();

        return LegacyStatementTerm::create([
            'batch_id' => $batch->id,
            'student_id' => $studentModel?->id,
            'admission_number' => $student['admission_number'],
            'student_name' => $student['name'],
            'academic_year' => $termHeader['academic_year'],
            'term_name' => $termHeader['term_name'],
            'term_number' => $termHeader['term_number'],
            'class_label' => $termHeader['class_label'] ?? $classLabel,
            'source_label' => $termHeader['source_label'],
            'status' => 'imported',
            'confidence' => 'high',
        ]);
    }

    private function isCurrentBalanceLine(string $line): bool
    {
        return stripos($line, 'CURRENT BALANCE') !== false;
    }

    private function extractAmountFromBalanceLine(string $line): ?float
    {
        if (preg_match('/CURRENT BALANCE:\s*([-\d,\.]+)/i', $line, $m)) {
            return $this->toAmount($m[1]);
        }

        return $this->extractTrailingAmount($line);
    }

    private function extractTrailingAmount(string $line): ?float
    {
        $parts = preg_split('/\s+/', trim($line));
        $last = Arr::last($parts);
        return $this->toAmount($last);
    }

    private function matchTransactionLine(string $line): ?array
    {
        if (!preg_match('/^(?<date>\d{2}-[A-Za-z]{3}-\d{4})\s+(?<rest>.+)$/', $line, $m)) {
            // Some rows may omit the date but still carry BALANCE BF or similar
            if (stripos($line, 'BALANCE BF') !== false) {
                return [
                    'date' => null,
                    'narration' => $line,
                    'amounts' => [
                        'dr' => null,
                        'cr' => null,
                        'rb' => null,
                    ],
                    'confidence' => 'draft',
                ];
            }
            return null;
        }

        $rest = trim($m['rest']);
        $amounts = $this->extractAmounts($rest);

        return [
            'date' => $m['date'],
            'narration' => $amounts['narration'] ?? $rest,
            'amounts' => [
                'dr' => $amounts['dr'] ?? null,
                'cr' => $amounts['cr'] ?? null,
                'rb' => $amounts['rb'] ?? null,
            ],
            'confidence' => $amounts['confidence'] ?? 'high',
        ];
    }

    private function extractAmounts(string $rest): array
    {
        $parts = preg_split('/\s{2,}/', $rest);
        $confidence = 'high';

        if (count($parts) >= 4) {
            $narration = array_shift($parts);
            $dr = $this->toAmount($parts[0] ?? null);
            $cr = $this->toAmount($parts[1] ?? null);
            $rb = $this->toAmount($parts[2] ?? null);
        } else {
            // Fallback: take last three tokens as amounts
            $tokens = preg_split('/\s+/', $rest);
            $rb = $this->toAmount(array_pop($tokens));
            $cr = $this->toAmount(array_pop($tokens));
            $dr = $this->toAmount(array_pop($tokens));
            $narration = trim(implode(' ', $tokens));
            $confidence = 'draft'; // layout uncertain
        }

        return [
            'narration' => trim($narration),
            'dr' => $dr,
            'cr' => $cr,
            'rb' => $rb,
            'confidence' => $confidence,
        ];
    }

    private function storeTransaction(LegacyFinanceImportBatch $batch, LegacyStatementTerm $term, array $txn, int $sequence): LegacyStatementLine
    {
        $date = $txn['date'] ? $this->parseDate($txn['date']) : null;
        $narration = $txn['narration'];
        $amountDr = $txn['amounts']['dr'];
        $amountCr = $txn['amounts']['cr'];
        $running = $txn['amounts']['rb'];

        $type = $this->detectType($narration);
        $votehead = $this->extractVotehead($narration);
        $ref = $this->extractReference($narration);
        $linkedInvoice = $this->inferLinkedInvoiceRef($narration, $term->id);
        $channel = $this->extractChannel($narration);
        $code = $this->extractTxnCode($narration);

        $confidence = $txn['confidence'];
        // If we have a clear amount on either side, treat as high confidence.
        if (($amountDr !== null && $amountCr === null) || ($amountCr !== null && $amountDr === null)) {
            $confidence = 'high';
        } elseif ($amountDr === null && $amountCr === null) {
            $confidence = 'draft';
        }

        $line = LegacyStatementLine::create([
            'batch_id' => $batch->id,
            'term_id' => $term->id,
            'txn_date' => $date,
            'narration_raw' => $narration,
            'txn_type' => $type,
            'votehead' => $votehead,
            'reference_number' => $ref,
            'linked_invoice_ref' => $linkedInvoice,
            'channel' => $channel,
            'txn_code' => $code,
            'amount_dr' => $amountDr,
            'amount_cr' => $amountCr,
            'running_balance' => $running,
            'confidence' => $confidence,
            'sequence_no' => $sequence,
        ]);

        return $line;
    }

    private function detectType(string $narration): string
    {
        $narr = strtoupper($narration);
        return match (true) {
            str_contains($narr, 'BALANCE BF') => 'balance_bf',
            str_contains($narr, 'RECEIPT REVERSAL') => 'debit_note',
            str_contains($narr, 'DEBIT NOTE') => 'debit_note',
            str_contains($narr, 'CREDIT NOTE') => 'credit_note',
            str_contains($narr, 'RECEIPT') => 'receipt',
            default => 'invoice',
        };
    }

    private function extractVotehead(string $narration): ?string
    {
        $parts = explode(' - ', $narration);
        return count($parts) ? trim($parts[0]) : null;
    }

    public function extractReference(string $narration): ?string
    {
        if (preg_match('/(INV\d+\/\d{4}\/\d+|REC\d+\/\d{4}|CR\/\d+\/\d+\/\d{4}|DR\/\d+\/\d+\/\d{4})/i', $narration, $m)) {
            return $m[1];
        }
        return null;
    }

    private function inferLinkedInvoiceRef(string $narration, int $termId): ?string
    {
        if (!preg_match('/CR\/\d+\/\d+\/\d{4}|DR\/\d+\/\d+\/\d{4}/i', $narration)) {
            return null;
        }

        // Attempt to find the nearest invoice reference inside the same term
        if (preg_match('/INV\d+\/\d{4}\/\d+/i', $narration, $m)) {
            return $m[0];
        }

        $previousInvoice = LegacyStatementLine::where('term_id', $termId)
            ->where('txn_type', 'invoice')
            ->orderByDesc('sequence_no')
            ->first();

        return $previousInvoice?->reference_number;
    }

    private function extractChannel(string $narration): ?string
    {
        if (preg_match('/-\s*(MPESA|CASH|BANK|CARD)/i', $narration, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    public function extractTxnCode(string $narration): ?string
    {
        if (preg_match('/\(([A-Z0-9]+)\)/', $narration, $m)) {
            return $m[1];
        }
        return null;
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d-M-Y', $date);
        } catch (\Throwable $e) {
            Log::warning('Legacy statement date parse failed', ['date' => $date, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function toAmount(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        $normalized = str_replace(',', '', $trimmed);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /**
     * Finalize ending balances for all terms in the batch.
     * For terms without an explicit ending_balance, use the running_balance from the last transaction line.
     */
    private function finalizeTermEndingBalances(int $batchId): void
    {
        $terms = LegacyStatementTerm::where('batch_id', $batchId)->get();

        foreach ($terms as $term) {
            // If ending_balance is already set, skip
            if ($term->ending_balance !== null) {
                continue;
            }

            // Get the last transaction line for this term (highest sequence_no)
            $lastLine = LegacyStatementLine::where('term_id', $term->id)
                ->whereNotNull('running_balance')
                ->orderBy('sequence_no', 'desc')
                ->first();

            if ($lastLine && $lastLine->running_balance !== null) {
                $term->ending_balance = $lastLine->running_balance;
                $term->save();
            }
        }
    }
}


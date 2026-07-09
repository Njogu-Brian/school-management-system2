<?php

namespace App\Services\Hr\PayrollImports;

use Smalot\PdfParser\Parser as PdfParser;

final class BudgetPdfParser
{
    /**
     * Parse your Budget PDF format into normalized rows.
     *
     * Expected columns (based on your June budget PDF):
     * name, gross, kids_fees, uniform, advance, loan, nssf, shif, paye, housing, total_deduction, net,
     * payment_method, phone_number, bank_account
     *
     * @return array<int,array<string,mixed>>
     */
    public function parse(string $absolutePdfPath): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($absolutePdfPath);
        $text = (string) $pdf->getText();

        $lines = preg_split("/\\R/u", $text) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\\s+/', ' ', (string) $line));
            if ($line === '' || str_contains(strtoupper($line), 'STAFF budget')) {
                continue;
            }
            // Skip headings
            if (preg_match('/^NAME\\s+GROSS/i', $line)) {
                continue;
            }
            // Most data lines begin with an index then name.
            // Example (from extracted text): "9 Tr. Mercy  30,000.0  1800  825.00 ..."
            if (!preg_match('/^(\\d+)\\s+(.+?)\\s+(\\d[\\d,]*\\.?\\d*)\\s+/u', $line, $m)) {
                continue;
            }

            $idx = (int) $m[1];
            $afterIdx = trim(substr($line, strlen($m[1])));
            // Tokenize numeric values while keeping name chunk.
            $tokens = preg_split('/\\s+/u', $afterIdx) ?: [];

            // Name is tokens until first numeric-ish token.
            $nameParts = [];
            $i = 0;
            for (; $i < count($tokens); $i++) {
                if ($this->looksNumeric($tokens[$i])) {
                    break;
                }
                $nameParts[] = $tokens[$i];
            }
            $name = trim(implode(' ', $nameParts));

            $nums = [];
            for (; $i < count($tokens); $i++) {
                if ($this->looksNumeric($tokens[$i])) {
                    $nums[] = $this->toFloat($tokens[$i]);
                }
            }

            // We only reliably get the core columns; payment method/phone/bank account often come later/are inconsistent.
            $rows[] = [
                'row_index' => $idx,
                'name' => $name,
                'gross' => $nums[0] ?? null,
                'kids_fees' => $nums[1] ?? 0.0,
                'uniform' => $nums[2] ?? 0.0,
                'advance' => $nums[3] ?? 0.0,
                'loan' => $nums[4] ?? 0.0,
                'nssf' => $nums[5] ?? 0.0,
                'shif' => $nums[6] ?? 0.0,
                'paye' => $nums[7] ?? 0.0,
                'housing' => $nums[8] ?? 0.0,
                'total_deduction' => $nums[9] ?? null,
                'net' => $nums[10] ?? null,
            ];
        }

        return $rows;
    }

    private function looksNumeric(string $t): bool
    {
        $t = str_replace([',', 'Ksh', 'KES'], '', $t);
        return (bool) preg_match('/^-?\\d+(?:\\.\\d+)?$/', $t);
    }

    private function toFloat(string $t): float
    {
        $t = str_replace([','], '', $t);
        return (float) $t;
    }
}


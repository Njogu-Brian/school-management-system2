<?php

namespace Database\Seeders;

use App\Models\ExpenseStatementLine;
use Illuminate\Database\Seeder;

/**
 * Decodes the cryptic, non-M-Pesa I&M statement narrations that were left
 * "uncategorised" after the initial import (VISA card purchases, salary &
 * loan transfers, utilities, eCitizen/NSSF, Airtel Money, etc.) and fills a
 * readable vendor / description IN PLACE.
 *
 * Safe & idempotent:
 *   - only touches lines from "bank" statement imports,
 *   - only PENDING lines (never re-touches anything you've classified),
 *   - only fills a BLANK vendor_name / expense_description (your edits stay).
 *
 * Run with:  php artisan db:seed --class=ImBankRefineSeeder
 */
class ImBankRefineSeeder extends Seeder
{
    public function run(): void
    {
        $updated = 0;

        ExpenseStatementLine::query()
            ->whereHas('import', fn ($q) => $q->where('source', 'bank'))
            ->where('direction', 'out')
            ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
            ->where('is_transaction_fee', false)
            ->chunkById(500, function ($lines) use (&$updated) {
                foreach ($lines as $line) {
                    $d = $this->decode((string) $line->narration);
                    $dirty = false;

                    if ($d['vendor'] && $this->isBlank($line->vendor_name)) {
                        $line->vendor_name = $d['vendor'];
                        if ($this->isBlank($line->recipient_name)) {
                            $line->recipient_name = $d['vendor'];
                        }
                        $dirty = true;
                    }
                    if ($d['desc'] && $this->isBlank($line->expense_description)) {
                        $line->expense_description = $d['desc'];
                        $dirty = true;
                    }
                    if ($d['phone'] && $this->isBlank($line->recipient_phone)) {
                        $line->recipient_phone = $d['phone'];
                        $dirty = true;
                    }
                    if ($d['receipt'] && $this->isBlank($line->receipt_no)) {
                        $line->receipt_no = $d['receipt'];
                        $dirty = true;
                    }
                    if ($d['type'] && $line->transaction_type === 'other') {
                        $line->transaction_type = $d['type'];
                        $dirty = true;
                    }

                    if ($dirty) {
                        $line->save();
                        $updated++;
                    }
                }
            });

        $this->command?->info("Refined {$updated} I&M statement line(s).");
    }

    /**
     * @return array{vendor: ?string, desc: ?string, type: ?string, phone: ?string, receipt: ?string}
     */
    private function decode(string $narration): array
    {
        $n = trim(preg_replace('/\s+/', ' ', $narration));
        $u = strtoupper($n);
        $out = ['vendor' => null, 'desc' => null, 'type' => null, 'phone' => null, 'receipt' => null];

        // VISA card purchases: "<MERCHANT ...> MMDD HHMMSSPRCR9830"
        if (preg_match('/PRCR\d+\s*$/i', $n)) {
            $merchant = preg_replace('/\s*\d{3,4}\s+\d{4,6}\s*PRCR\d+\s*$/i', '', $n);
            $out['vendor'] = $this->title(trim($merchant));
            $out['desc'] = 'Card purchase';
            $out['type'] = 'buy_goods';
            return $out;
        }

        // eCitizen / government: "JLNBRRWE-ECITIZEN", "REQBWLPV-ECITIZEN nssf"
        if (preg_match('/^([A-Z0-9]{6,12})-ECITIZEN(.*)$/i', $n, $m)) {
            $out['receipt'] = strtoupper($m[1]);
            $extra = trim($m[2]);
            $out['vendor'] = 'eCitizen';
            $out['desc'] = $extra !== '' ? 'eCitizen - ' . $this->title($extra) : 'eCitizen / government payment';
            $out['type'] = 'paybill';
            return $out;
        }

        // Airtel Money to <phone>
        if (preg_match('/^Airtel Money to (\d{9,12})/i', $n, $m)) {
            $out['phone'] = $this->normPhone($m[1]);
            $out['desc'] = 'Airtel Money transfer';
            $out['type'] = 'send_money';
            return $out;
        }

        // Purpose-tagged transfers: "<account>/Salary Payment", "/Loan Repayment", "/Utilities", "/tithe", ...
        if (preg_match('#^\d+/(.+)$#', $n, $m)) {
            $purpose = trim($m[1]);
            $pu = strtoupper($purpose);
            if (str_starts_with($pu, 'MPESA PAYMENT')) {
                return $out; // plain M-Pesa send, nothing to add
            }
            if (str_contains($pu, 'SALARY')) {
                $out['desc'] = 'Salary payment';
            } elseif (str_contains($pu, 'LOAN')) {
                $out['desc'] = 'Loan repayment';
            } elseif (str_contains($pu, 'UTILIT')) {
                $out['desc'] = 'Utilities';
            } elseif (str_contains($pu, 'TITHE')) {
                $out['desc'] = 'Tithe';
            } else {
                $out['desc'] = $this->title($purpose);
            }
            return $out;
        }

        // Loan mechanics
        if (str_contains($u, 'DEBIT FROM PAYOFF SOURCE') || str_contains($u, 'LOAN RECOVERY')) {
            $out['desc'] = 'Loan repayment';
            return $out;
        }

        // Account-to-account transfer
        if (str_starts_with($u, 'PAYMENT TO ')) {
            $out['desc'] = 'Account transfer';
            return $out;
        }

        // Bare paybill / account number
        if (preg_match('/^\d{6,}$/', $n)) {
            $out['desc'] = 'Paybill / account payment';
            $out['type'] = 'paybill';
            return $out;
        }

        return $out;
    }

    private function isBlank(?string $v): bool
    {
        return $v === null || trim($v) === '';
    }

    private function title(string $s): string
    {
        $s = trim($s);
        return $s === '' ? $s : mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8');
    }

    private function normPhone(string $p): string
    {
        $d = preg_replace('/\D/', '', $p);
        if (strlen($d) === 9) {
            return '254' . $d;
        }
        if (str_starts_with($d, '0')) {
            return '254' . substr($d, 1);
        }
        return $d;
    }
}

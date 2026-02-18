<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ReviewDuplicateTransactions extends Command
{
    protected $signature = 'finance:review-duplicates
                            {--export= : Optional path to export report as CSV}
                            {--fix : Fix issues: clear broken links and unmark duplicates with no resolvable original (move them back to All for processing)}';

    protected $description = 'Review all duplicate transactions: confirm fees duplicates have existing payment, swimming originals appear in swimming tab';

    public function handle(): int
    {
        $doFix = $this->option('fix');
        if ($doFix) {
            $this->info('Applying fixes to duplicate transactions...');
            $unmarkedBank = 0;
            $unmarkedC2b = 0;
            $backfilledBank = 0;

            // ---- Bank: fix broken duplicate_of_payment_id links & unmark orphans ----
            $bankRowsToFix = BankStatementTransaction::where('is_duplicate', true)->get();
            foreach ($bankRowsToFix as $row) {
                if ($row->duplicate_of_payment_id) {
                    $payment = Payment::where('id', $row->duplicate_of_payment_id)->first();
                    $paymentOk = $payment && !$payment->reversed;
                    $original = BankStatementTransaction::where('payment_id', $row->duplicate_of_payment_id)->where('is_duplicate', false)->first();
                    if (!$paymentOk || !$original) {
                        $row->update([
                            'is_duplicate' => false,
                            'duplicate_of_payment_id' => null,
                            'duplicate_of_transaction_id' => null,
                        ]);
                        $unmarkedBank++;
                        $this->line("Unmarked bank #{$row->id} ({$row->reference_number}): no resolvable original (payment missing or no original bank row).");
                    }
                } else {
                    // No payment link: try to backfill duplicate_of_transaction_id from group
                    $original = BankStatementTransaction::where('reference_number', $row->reference_number)
                        ->where('amount', $row->amount)
                        ->whereDate('transaction_date', $row->transaction_date)
                        ->where('id', '!=', $row->id)
                        ->where('is_duplicate', false)
                        ->orderBy('id')
                        ->first();
                    if (!$original) {
                        $original = BankStatementTransaction::where('reference_number', $row->reference_number)
                            ->where('amount', $row->amount)
                            ->whereDate('transaction_date', $row->transaction_date)
                            ->where('id', '<', $row->id)
                            ->orderBy('id')
                            ->first();
                    }
                    if ($original && Schema::hasColumn('bank_statement_transactions', 'duplicate_of_transaction_id')) {
                        $row->update(['duplicate_of_transaction_id' => $original->id]);
                        $backfilledBank++;
                    } elseif (!$original) {
                        $row->update([
                            'is_duplicate' => false,
                            'duplicate_of_payment_id' => null,
                            'duplicate_of_transaction_id' => null,
                        ]);
                        $unmarkedBank++;
                        $this->line("Unmarked bank #{$row->id} ({$row->reference_number}): no original found in group.");
                    }
                }
            }

            // ---- C2B: unmark orphans and false positives (wrong trans_id match) ----
            $c2bRowsToFix = MpesaC2BTransaction::where('is_duplicate', true)->get();
            foreach ($c2bRowsToFix as $row) {
                if ($row->duplicate_of) {
                    $original = MpesaC2BTransaction::find($row->duplicate_of);
                    if (!$original) {
                        $row->update(['is_duplicate' => false, 'duplicate_of' => null]);
                        $unmarkedC2b++;
                        $this->line("Unmarked C2B #{$row->id} (trans_id {$row->trans_id}): original C2B #{$row->duplicate_of} not found.");
                    } elseif ($original->trans_id !== $row->trans_id) {
                        // False positive: marked as duplicate of different transaction (phone+amount+time heuristic was wrong)
                        $row->update(['is_duplicate' => false, 'duplicate_of' => null, 'status' => 'pending', 'allocation_status' => 'unallocated']);
                        $unmarkedC2b++;
                        $this->line("Unmarked C2B #{$row->id} (trans_id {$row->trans_id}): false positive - original #{$original->id} has different trans_id ({$original->trans_id}).");
                    }
                } else {
                    $bankOrig = BankStatementTransaction::where('reference_number', $row->trans_id)->first();
                    if (!$bankOrig) {
                        $row->update(['is_duplicate' => false, 'duplicate_of' => null]);
                        $unmarkedC2b++;
                        $this->line("Unmarked C2B #{$row->id} (trans_id {$row->trans_id}): no bank original found (cross-type orphan).");
                    }
                }
            }

            if ($unmarkedBank > 0 || $unmarkedC2b > 0 || $backfilledBank > 0) {
                $this->info("Fix complete: unmarked {$unmarkedBank} bank + {$unmarkedC2b} C2B duplicate(s) with no original; backfilled {$backfilledBank} bank duplicate_of_transaction_id link(s).");
                $this->info('Unmarked transactions now appear in All tab and can be processed.');
            }
            $this->newLine();
        }

        $this->info('Reviewing duplicate transactions (fees & swimming)...');
        $this->newLine();

        $issues = [];
        $bankDuplicates = [];
        $c2bDuplicates = [];

        // ---- Bank statement duplicates ----
        if (Schema::hasTable('bank_statement_transactions')) {
            $bankRows = BankStatementTransaction::where('is_duplicate', true)
                ->with(['duplicateOfPayment', 'payment'])
                ->orderBy('id')
                ->get();

            foreach ($bankRows as $row) {
                $rec = [
                    'type' => 'bank',
                    'id' => $row->id,
                    'reference' => $row->reference_number,
                    'amount' => $row->amount,
                    'date' => $row->transaction_date?->format('Y-m-d'),
                    'duplicate_of_payment_id' => $row->duplicate_of_payment_id,
                    'is_swimming' => (bool) ($row->is_swimming_transaction ?? false),
                    'payment_ok' => false,
                    'original_found' => false,
                    'note' => '',
                ];
                if ($row->duplicate_of_payment_id) {
                    $payment = Payment::where('id', $row->duplicate_of_payment_id)->first();
                    if ($payment) {
                        $rec['payment_ok'] = !$payment->reversed;
                        $rec['note'] = $payment->reversed ? 'Payment is reversed' : 'OK';
                        // Original bank row that has this payment
                        $original = BankStatementTransaction::where('payment_id', $row->duplicate_of_payment_id)
                            ->where('is_duplicate', false)
                            ->first();
                        $rec['original_found'] = (bool) $original;
                        if ($original && Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
                            $rec['is_swimming'] = (bool) $original->is_swimming_transaction;
                        }
                    } else {
                        $rec['note'] = 'Duplicate-of payment not found';
                    }
                } else {
                    $rec['note'] = 'No duplicate_of_payment_id set';
                }
                $bankDuplicates[] = $rec;
                // Only flag: missing/reversed payment, or link to payment that no original bank row has
                if (!$rec['payment_ok'] && $rec['note'] !== 'No duplicate_of_payment_id set') {
                    $issues[] = "Bank #{$row->id} ({$row->reference_number}): {$rec['note']}";
                } elseif ($rec['duplicate_of_payment_id'] && !$rec['original_found']) {
                    $issues[] = "Bank #{$row->id} ({$row->reference_number}): Original bank transaction not found for payment #{$row->duplicate_of_payment_id}";
                }
            }
        }

        // ---- C2B duplicates ----
        if (Schema::hasTable('mpesa_c2b_transactions')) {
            $hasC2bSwimming = Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction');
            $c2bRows = MpesaC2BTransaction::where('is_duplicate', true)
                ->with(['duplicateOf', 'payment'])
                ->orderBy('id')
                ->get();

            foreach ($c2bRows as $row) {
                $rec = [
                    'type' => 'c2b',
                    'id' => $row->id,
                    'trans_id' => $row->trans_id,
                    'amount' => $row->trans_amount,
                    'date' => $row->trans_time?->format('Y-m-d'),
                    'duplicate_of_id' => $row->duplicate_of,
                    'is_swimming' => false,
                    'payment_ok' => false,
                    'in_swimming_tab' => false,
                    'note' => '',
                ];
                if ($row->duplicate_of) {
                    $original = MpesaC2BTransaction::find($row->duplicate_of);
                    if ($original) {
                        $rec['is_swimming'] = $hasC2bSwimming && (bool) $original->is_swimming_transaction;
                        $rec['in_swimming_tab'] = $rec['is_swimming']; // would show in swimming view
                        if ($original->payment_id) {
                            $pay = Payment::where('id', $original->payment_id)->where('reversed', false)->first();
                            $rec['payment_ok'] = (bool) $pay;
                            $rec['note'] = $rec['is_swimming'] ? 'Original is swimming' : ($rec['payment_ok'] ? 'Original has payment' : 'Original payment missing/reversed');
                        } else {
                            $byRef = Payment::where('reversed', false)
                                ->where(function ($q) use ($original) {
                                    $q->where('transaction_code', $original->trans_id)
                                        ->orWhere('transaction_code', 'LIKE', $original->trans_id . '-%');
                                })
                                ->exists();
                            $rec['payment_ok'] = $byRef;
                            $rec['note'] = $rec['is_swimming'] ? 'Original is swimming' : ($byRef ? 'Payment found by ref' : 'No payment for original');
                        }
                    } else {
                        $rec['note'] = 'Original C2B #' . $row->duplicate_of . ' not found';
                        $issues[] = "C2B #{$row->id} (trans_id {$row->trans_id}): {$rec['note']}";
                    }
                    if ($original && $rec['is_swimming'] && !$rec['in_swimming_tab']) {
                        $issues[] = "C2B #{$row->id} (swimming): original should appear in swimming tab";
                    }
                    if ($original && !$rec['is_swimming'] && !$rec['payment_ok']) {
                        $issues[] = "C2B #{$row->id} (fees): original has no existing payment";
                    }
                } else {
                    // Cross-type: duplicate of bank statement (reference = trans_id)
                    // Prefer non-duplicate bank row; if none, use first duplicate bank row with same ref (so report can show "bank original has payment")
                    $bankOrig = BankStatementTransaction::where('reference_number', $row->trans_id)
                        ->where('is_duplicate', false)
                        ->first();
                    if (!$bankOrig) {
                        $bankOrig = BankStatementTransaction::where('reference_number', $row->trans_id)
                            ->orderBy('id')
                            ->first();
                    }
                    if ($bankOrig) {
                        $rec['duplicate_of_id'] = 'bank:' . $bankOrig->id;
                        $rec['is_swimming'] = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction') && (bool) $bankOrig->is_swimming_transaction;
                        $rec['in_swimming_tab'] = $rec['is_swimming'];
                        $rec['payment_ok'] = (bool) $bankOrig->payment_id || (bool) ($bankOrig->payment_created ?? false);
                        if (!$rec['payment_ok'] && !empty($bankOrig->duplicate_of_payment_id)) {
                            $rec['payment_ok'] = Payment::where('id', $bankOrig->duplicate_of_payment_id)->where('reversed', false)->exists();
                        }
                        if (!$rec['payment_ok']) {
                            $byRef = Payment::where('reversed', false)
                                ->where(function ($q) use ($row) {
                                    $q->where('transaction_code', $row->trans_id)
                                        ->orWhere('transaction_code', 'LIKE', $row->trans_id . '-%');
                                })
                                ->exists();
                            $rec['payment_ok'] = $byRef;
                        }
                        $rec['note'] = $rec['is_swimming'] ? 'Original is bank swimming' : ($rec['payment_ok'] ? 'Bank original has payment' : 'Bank original has no payment');
                        if (!$rec['is_swimming'] && !$rec['payment_ok']) {
                            $issues[] = "C2B #{$row->id} (cross-type fees): bank original has no payment";
                        }
                    } else {
                        $rec['note'] = 'Cross-type: no bank original found for trans_id ' . $row->trans_id;
                        $issues[] = "C2B #{$row->id} (trans_id {$row->trans_id}): {$rec['note']}";
                    }
                }
                $c2bDuplicates[] = $rec;
            }
        }

        // ---- Summary table ----
        $this->table(
            ['Type', 'ID', 'Ref/TransId', 'Amount', 'Date', 'Original', 'Fees payment OK?', 'Swimming in tab?', 'Note'],
            array_merge(
                array_map(function ($r) {
                    return [
                        $r['type'],
                        $r['id'],
                        $r['type'] === 'bank' ? ($r['reference'] ?? '') : ($r['trans_id'] ?? ''),
                        $r['amount'] ?? '',
                        $r['date'] ?? '',
                        $r['type'] === 'bank' ? ($r['duplicate_of_payment_id'] ?? '') : ($r['duplicate_of_id'] ?? ''),
                        $r['type'] === 'bank' ? ($r['payment_ok'] ? 'Yes' : 'No') : ($r['payment_ok'] ? 'Yes' : 'No'),
                        $r['type'] === 'bank' ? ($r['is_swimming'] ? 'Yes' : 'No') : ($r['in_swimming_tab'] ? 'Yes' : 'No'),
                        $r['note'],
                    ];
                }, $bankDuplicates),
                array_map(function ($r) {
                    return [
                        $r['type'],
                        $r['id'],
                        $r['trans_id'] ?? '',
                        $r['amount'] ?? '',
                        $r['date'] ?? '',
                        $r['duplicate_of_id'] ?? '',
                        $r['payment_ok'] ? 'Yes' : 'No',
                        $r['in_swimming_tab'] ? 'Yes' : 'No',
                        $r['note'],
                    ];
                }, $c2bDuplicates)
            )
        );

        $totalBank = count($bankDuplicates);
        $totalC2b = count($c2bDuplicates);
        $this->newLine();
        $this->info("Summary: {$totalBank} bank duplicate(s), {$totalC2b} C2B duplicate(s).");
        $exportPath = $this->option('export');
        if ($exportPath) {
            $all = array_merge(
                array_map(function ($r) {
                    return [
                        'type' => $r['type'],
                        'id' => $r['id'],
                        'ref' => $r['type'] === 'bank' ? ($r['reference'] ?? '') : ($r['trans_id'] ?? ''),
                        'amount' => $r['amount'],
                        'date' => $r['date'],
                        'original' => $r['type'] === 'bank' ? ($r['duplicate_of_payment_id'] ?? '') : ($r['duplicate_of_id'] ?? ''),
                        'payment_ok' => $r['payment_ok'] ? 'Yes' : 'No',
                        'swimming_in_tab' => ($r['type'] === 'bank' ? ($r['is_swimming'] ?? false) : ($r['in_swimming_tab'] ?? false)) ? 'Yes' : 'No',
                        'note' => $r['note'],
                    ];
                }, $bankDuplicates),
                array_map(function ($r) {
                    return [
                        'type' => $r['type'],
                        'id' => $r['id'],
                        'ref' => $r['trans_id'] ?? '',
                        'amount' => $r['amount'],
                        'date' => $r['date'],
                        'original' => $r['duplicate_of_id'] ?? '',
                        'payment_ok' => $r['payment_ok'] ? 'Yes' : 'No',
                        'swimming_in_tab' => ($r['in_swimming_tab'] ?? false) ? 'Yes' : 'No',
                        'note' => $r['note'],
                    ];
                }, $c2bDuplicates)
            );
            $fp = fopen($exportPath, 'w');
            if ($fp) {
                fputcsv($fp, ['Type', 'ID', 'Ref/TransId', 'Amount', 'Date', 'Original', 'Payment OK', 'Swimming in tab', 'Note']);
                foreach ($all as $r) {
                    fputcsv($fp, array_values($r));
                }
                fclose($fp);
                $this->info('Report exported to: ' . $exportPath);
            }
        }

        if (!empty($issues)) {
            $this->newLine();
            $this->warn('Issues found (' . count($issues) . '):');
            foreach (array_slice($issues, 0, 50) as $msg) {
                $this->line('  - ' . $msg);
            }
            if (count($issues) > 50) {
                $this->line('  ... and ' . (count($issues) - 50) . ' more.');
            }
            return self::FAILURE;
        }
        $this->info('All duplicates verified: fees have existing payment where applicable; swimming originals are in swimming tab.');
        return self::SUCCESS;
    }
}

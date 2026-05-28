<?php

namespace App\Services;

use App\Models\{Student, Invoice, InvoiceItem, Votehead};
use Illuminate\Support\Facades\DB;

class SiblingBalanceTransferService
{
    /**
     * Transfer outstanding invoice balance from one student to another.
     *
     * Accounting model:
     * - Reduce ("credit") the FROM student's outstanding invoice items by reducing item amounts
     *   via InvoiceService::updateItemAmount (which creates credit notes/debit notes + audit trail).
     * - Add a new invoice item ("debit") to the TO student's current term invoice.
     *
     * This keeps the ledger consistent and ensures invoice balances recalc correctly.
     */
    public function transferOutstandingBalance(Student $fromStudent, Student $toStudent, ?int $actorId = null): array
    {
        // Ensure we can access archived students (Student has a global "active" scope)
        $fromStudent = Student::withArchived()->findOrFail($fromStudent->id);
        $toStudent = Student::withArchived()->findOrFail($toStudent->id);

        if ((int) $fromStudent->id === (int) $toStudent->id) {
            throw new \InvalidArgumentException('Cannot transfer balance to the same student.');
        }

        if (!$fromStudent->archive) {
            throw new \InvalidArgumentException('Source student must be archived to transfer their balance.');
        }

        if ($toStudent->archive || $toStudent->is_alumni) {
            throw new \InvalidArgumentException('Target student must be active (not archived/alumni).');
        }

        $fromInvoiceBalance = (float) StudentBalanceService::getInvoiceBalance($fromStudent);
        if ($fromInvoiceBalance <= 0.01) {
            return [
                'transferred_amount' => 0.0,
                'message' => 'No outstanding invoice balance to transfer.',
            ];
        }

        $year = (int) (setting('current_year') ?? date('Y'));
        $term = (int) (get_current_term_number() ?? 1);

        return DB::transaction(function () use ($fromStudent, $toStudent, $fromInvoiceBalance, $year, $term, $actorId) {
            $remainingToClear = $fromInvoiceBalance;

            // Clear FROM student's balances by reducing unpaid invoice items (oldest first).
            $fromInvoices = Invoice::where('student_id', $fromStudent->id)
                ->where('status', '!=', 'reversed')
                ->whereNull('reversed_at')
                ->with(['items.allocations'])
                ->orderBy('issued_date')
                ->orderBy('id')
                ->get();

            $clearedItems = 0;
            foreach ($fromInvoices as $invoice) {
                // Work on active items only; skip any non-positive balance items.
                $items = $invoice->items
                    ->filter(fn (InvoiceItem $i) => ($i->status ?? 'active') === 'active')
                    ->sortBy('id')
                    ->values();

                foreach ($items as $item) {
                    if ($remainingToClear <= 0.01) {
                        break 2;
                    }

                    $balance = (float) $item->getBalance();
                    if ($balance <= 0.01) {
                        continue;
                    }

                    $reduceBy = min($balance, $remainingToClear);
                    $newAmount = (float) $item->amount - $reduceBy;

                    // Safety: never reduce below (allocated + discount) to avoid negative item balance.
                    $allocated = (float) $item->allocations->sum('amount');
                    $discount = (float) ($item->discount_amount ?? 0);
                    $minAmount = $allocated + $discount;
                    if ($newAmount < $minAmount) {
                        $newAmount = $minAmount;
                        $reduceBy = (float) $item->amount - $newAmount;
                    }

                    if ($reduceBy <= 0.0001) {
                        continue;
                    }

                    InvoiceService::updateItemAmount(
                        $item->fresh(),
                        (float) $newAmount,
                        'Fee balance transfer',
                        'Transferred to ' . $toStudent->full_name . ' (' . ($toStudent->admission_number ?? ('ID ' . $toStudent->id)) . ')'
                    );

                    $clearedItems++;
                    $remainingToClear -= $reduceBy;
                }
            }

            $transferred = $fromInvoiceBalance - max(0.0, $remainingToClear);

            if ($transferred <= 0.01) {
                return [
                    'transferred_amount' => 0.0,
                    'message' => 'Could not transfer balance (no eligible invoice items found).',
                ];
            }

            // Add transferred balance as a new charge on TO student's current term invoice.
            $votehead = Votehead::firstOrCreate(
                ['code' => 'BAL_TRF'],
                ['name' => 'Balance Transfer', 'is_active' => true]
            );

            $toInvoice = InvoiceService::ensure($toStudent->id, $year, $term);

            $item = \App\Models\InvoiceItem::create([
                'invoice_id' => $toInvoice->id,
                'votehead_id' => $votehead->id,
                'amount' => $transferred,
                'discount_amount' => 0,
                'original_amount' => $transferred,
                'status' => 'active',
                'source' => 'balance_transfer',
                'effective_date' => now()->toDateString(),
                'posted_at' => now(),
            ]);

            InvoiceService::recalc($toInvoice);
            InvoiceService::allocateUnallocatedPaymentsForStudent($toInvoice->student_id);

            // Best-effort audit
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log('created', $item, [], [
                    'from_student_id' => $fromStudent->id,
                    'to_student_id' => $toStudent->id,
                    'amount' => $transferred,
                    'actor_id' => $actorId,
                ], ['balance_transfer']);
            }

            return [
                'transferred_amount' => (float) $transferred,
                'cleared_items_count' => $clearedItems,
                'from_student_id' => $fromStudent->id,
                'to_student_id' => $toStudent->id,
                'to_invoice_id' => $toInvoice->id,
            ];
        });
    }
}


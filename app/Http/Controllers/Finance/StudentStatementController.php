<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\FeeConcession;
use App\Models\LegacyStatementLine;
use App\Models\PaymentAllocation;
use App\Models\LegacyStatementLineEditHistory;
use App\Models\InvoiceItem;
use App\Models\Votehead;
use App\Services\InvoiceService;
use App\Services\LegacyStatementRecalcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StudentStatementController extends Controller
{
    public function index()
    {
        return view('finance.student_statements.index');
    }

    public function show(Request $request, Student $student)
    {
        // Eager load classroom and stream relationships
        $student->load(['classroom', 'stream']);
        
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        
        // Get all invoices for the student with detailed items (exclude reversed).
        // Match by year column or by academic_year relation so we include invoices that
        // were created with only academic_year_id set (e.g. fee posting) and have year=null.
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where(function ($q) use ($year) {
                $q->where('year', $year)
                  ->orWhereHas('academicYear', function ($q2) use ($year) {
                      $q2->where('year', $year);
                  });
            })
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            })
            ->with([
                'items.votehead', 
                'items.creditNotes', 
                'items.debitNotes',
                'items.allocations.payment',
                'term', 
                'academicYear'
            ]);
            
        if ($term) {
            $invoicesQuery->whereHas('term', function($q) use ($term) {
                $q->where('name', 'like', "%Term {$term}%")
                  ->orWhere('id', $term);
            });
        }
        
        $invoices = $invoicesQuery->orderBy('created_at')->get();

        // Get all payments with allocations. For 2026+ with term: include any payment that has
        // at least one allocation to the displayed invoices (so the transaction list matches Total Payments).
        $invoiceIds = $invoices->pluck('id')->toArray();
        $paymentsQuery = Payment::where('student_id', $student->id)
            ->with(['allocations.invoiceItem.votehead', 'allocations.invoiceItem.invoice']);

        if ($year >= 2026) {
            // For modern years, include payments allocated to the displayed invoices (ignore payment_date year).
            if (!empty($invoiceIds)) {
                $paymentsQuery->whereHas('allocations', function ($q) use ($invoiceIds) {
                    $q->whereHas('invoiceItem', function ($q2) use ($invoiceIds) {
                        $q2->whereIn('invoice_id', $invoiceIds);
                    });
                });
            } else {
                $paymentsQuery->whereRaw('1 = 0'); // no invoices => no payments to show
            }
        } else {
            // Legacy years rely on payment date year and term filter (if any).
            $paymentsQuery->whereYear('payment_date', $year);
            if ($term) {
                $paymentsQuery->where(function ($q) use ($term) {
                    $q->whereHas('invoice', function ($q2) use ($term) {
                        $q2->whereHas('term', function ($q3) use ($term) {
                            $q3->where('name', 'like', "%Term {$term}%")
                               ->orWhere('id', $term);
                        });
                    })->orWhereNull('invoice_id');
                });
            }
        }

        $payments = $paymentsQuery->orderBy('payment_date')->get();

        // Get all discounts
        $discountsQuery = FeeConcession::where('student_id', $student->id)
            ->where('year', $year)
            ->where('approval_status', 'approved')
            ->with('discountTemplate');
            
        if ($term) {
            $discountsQuery->where('term', $term);
        }
        
        $discounts = $discountsQuery->orderBy('created_at')->get();

        // Exclude only daily-attendance swimming (source=swimming_attendance). Term swimming fees + their credit/debit notes are included.
        $excludeSourceSwimmingAttendance = function ($q) {
            $q->where(function ($q2) {
                $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
            });
        };

        // Get all credit notes (exclude only those tied to daily-attendance debits)
        $creditNotesQuery = CreditNote::whereHas('invoiceItem', function($q) use ($student, $excludeSourceSwimmingAttendance) {
            $q->whereHas('invoice', function($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
            $excludeSourceSwimmingAttendance($q);
        })->whereYear('created_at', $year)->with(['invoiceItem.invoice', 'invoiceItem.votehead', 'issuedBy']);

        if ($term) {
            $creditNotesQuery->whereHas('invoiceItem.invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")
                       ->orWhere('id', $term);
                });
            });
        }

        $creditNotes = $creditNotesQuery->orderBy('created_at')->get();

        // Get all debit notes (exclude only those tied to daily-attendance debits)
        $debitNotesQuery = DebitNote::whereHas('invoiceItem', function($q) use ($student, $excludeSourceSwimmingAttendance) {
            $q->whereHas('invoice', function($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
            $excludeSourceSwimmingAttendance($q);
        })->whereYear('created_at', $year)->with(['invoiceItem.invoice', 'invoiceItem.votehead', 'issuedBy']);

        if ($term) {
            $debitNotesQuery->whereHas('invoiceItem.invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")
                       ->orWhere('id', $term);
                });
            });
        }

        $debitNotes = $debitNotesQuery->orderBy('created_at')->get();

        $swimmingPaymentIds = $this->getSwimmingPaymentIdsForStatement();

        // Detect if balance brought forward is already represented as an invoice item
        $hasBalanceBroughtForwardInInvoices = $invoices->flatMap->items->contains(function ($item) {
            $voteheadCode = $item->votehead->code ?? null;
            return ($item->source ?? null) === 'balance_brought_forward' || $voteheadCode === 'BAL_BF';
        });
        
        // Recalculate all invoices to ensure accurate balances
        foreach ($invoices as $invoice) {
            $invoice->recalculate();
        }
        
        // Legacy transactions: show entries for the selected year
        $legacyLines = LegacyStatementLine::with('term')
            ->whereHas('term', function ($q) use ($student, $year) {
                $q->where('student_id', $student->id)
                  ->where('academic_year', $year);
            })
            ->orderBy('txn_date')
            ->orderBy('sequence_no')
            ->get();

        $legacyTransactions = collect();
        foreach ($legacyLines as $line) {
            $legacyTransactions->push([
                'date' => $line->txn_date ?? $line->created_at,
                'type' => 'Legacy',
                'description' => $line->narration_raw,
                'reference' => $line->reference_number ?? $line->txn_code ?? 'Legacy',
                'votehead' => $line->votehead ?? 'Legacy',
                'debit' => (float) ($line->amount_dr ?? 0),
                'credit' => (float) ($line->amount_cr ?? 0),
                'model_type' => 'LegacyStatementLine',
                'model_id' => $line->id,
                'legacy_balance' => $line->running_balance,
            ]);
        }

        // Build detailed transaction list with votehead-level items
        $detailedTransactions = collect()->merge($legacyTransactions);

        // For 2026+, prepend Balance Brought Forward from legacy (positive = debit owed, negative = credit/overpayment)
        if ($year >= 2026 && !$hasBalanceBroughtForwardInInvoices) {
            $legacyBbf = \App\Models\LegacyStatementTerm::getBalanceBroughtForward($student);
            if ($legacyBbf !== null && abs((float) $legacyBbf) >= 0.01) {
                $bfDate = \Carbon\Carbon::createFromDate((int) $year, 1, 1)->startOfDay();
                if ((float) $legacyBbf > 0) {
                    $detailedTransactions->prepend([
                        'date' => $bfDate,
                        'type' => 'Balance Brought Forward',
                        'description' => 'Balance brought forward from ' . ($year - 1),
                        'reference' => 'BBF-' . ($year - 1),
                        'votehead' => 'Balance Brought Forward',
                        'debit' => (float) $legacyBbf,
                        'credit' => 0,
                        'model_type' => 'LegacyStatementTerm',
                        'model_id' => null,
                        'legacy_balance' => (float) $legacyBbf,
                    ]);
                } else {
                    $detailedTransactions->prepend([
                        'date' => $bfDate,
                        'type' => 'Balance Brought Forward',
                        'description' => 'Overpayment brought forward from ' . ($year - 1),
                        'reference' => 'BBF-' . ($year - 1),
                        'votehead' => 'Balance Brought Forward',
                        'debit' => 0,
                        'credit' => abs((float) $legacyBbf),
                        'model_type' => 'LegacyStatementTerm',
                        'model_id' => null,
                        'legacy_balance' => (float) $legacyBbf,
                    ]);
                }
            }
        }
        
        // 1. Add invoice items (each votehead as separate line item). Exclude daily-attendance swimming (source=swimming_attendance).
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                if (($item->source ?? null) === 'swimming_attendance') {
                    continue;
                }
                $itemDate = $item->posted_at ?? $item->effective_date ?? $item->created_at ?? $invoice->created_at;
                $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                $itemAmount = $item->amount ?? 0;
                $discountAmount = $item->discount_amount ?? 0;
                
                if ($itemAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Invoice Item',
                        'description' => $voteheadName . ' - ' . ($invoice->term->name ?? 'Term') . ' ' . $year,
                        'reference' => $invoice->invoice_number,
                        'votehead' => $voteheadName,
                        'debit' => $itemAmount,
                        'credit' => 0,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $item->id,
                        'model_type' => 'InvoiceItem',
                        'model_id' => $item->id,
                    ]);
                } elseif ($itemAmount < 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Balance Brought Forward',
                        'description' => 'Overpayment - ' . $voteheadName,
                        'reference' => $invoice->invoice_number,
                        'votehead' => $voteheadName,
                        'debit' => 0,
                        'credit' => abs($itemAmount),
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $item->id,
                        'model_type' => 'InvoiceItem',
                        'model_id' => $item->id,
                    ]);
                }
                
                // Add item-level discounts as separate line items
                if ($discountAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Discount',
                        'description' => 'Discount - ' . $voteheadName,
                        'reference' => $invoice->invoice_number,
                        'votehead' => $voteheadName,
                        'debit' => 0,
                        'credit' => $discountAmount,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $item->id,
                        'model_type' => 'InvoiceItem',
                        'model_id' => $item->id,
                    ]);
                }
            }
            
            // Add invoice-level discounts
            if ($invoice->discount_amount > 0) {
                $detailedTransactions->push([
                    'date' => $invoice->created_at,
                    'type' => 'Discount',
                    'description' => 'Invoice Discount - ' . $invoice->invoice_number,
                    'reference' => $invoice->invoice_number,
                    'votehead' => 'All Voteheads',
                    'debit' => 0,
                    'credit' => $invoice->discount_amount,
                    'invoice_id' => $invoice->id,
                    'model_type' => 'Invoice',
                    'model_id' => $invoice->id,
                ]);
            }
        }
        
        // 2. Add payment allocations (each votehead payment as separate line item). Exclude payments marked as swimming.
        $swimmingPaymentIdsSet = array_flip($swimmingPaymentIds);
        $studentInvoiceIds = $invoices->pluck('id')->flip();
        foreach ($payments as $payment) {
            if (isset($swimmingPaymentIdsSet[$payment->id])) {
                continue;
            }
            if ($payment->reversed) {
                // For reversed payments, we need to show what was reversed
                // Get the original allocations from payment history or show as reversal
                // Since allocations are deleted on reversal, we'll show the reversal with the original payment amount
                // Try to get original allocation info if available (from audit log or payment notes)
                $reversalDate = $payment->reversed_at ?? $payment->updated_at;
                
                // Show reversal per votehead if we can determine them, otherwise show as single reversal
                // For now, show as single reversal entry
                $detailedTransactions->push([
                    'date' => $reversalDate,
                    'type' => 'Payment Reversal',
                    'description' => 'Payment Reversed - ' . ($payment->paymentMethod->name ?? 'N/A') . ' (Original: ' . $payment->receipt_number . ')',
                    'reference' => $payment->receipt_number . '-REV',
                    'votehead' => 'All Voteheads',
                    'debit' => $payment->amount, // Reversal increases balance (debit)
                    'credit' => 0,
                    'payment_id' => $payment->id,
                    'model_type' => 'Payment',
                    'model_id' => $payment->id,
                    'is_reversal' => true,
                ]);
            } else {
                // Show each payment allocation per votehead
                if ($payment->allocations->isEmpty()) {
                    // Unallocated payment
                    $detailedTransactions->push([
                        'date' => $payment->payment_date,
                        'type' => 'Payment',
                        'description' => 'Payment - ' . ($payment->paymentMethod->name ?? 'N/A') . ' (Unallocated)',
                        'reference' => $payment->receipt_number,
                        'votehead' => 'Unallocated',
                        'debit' => 0,
                        'credit' => $payment->amount,
                        'payment_id' => $payment->id,
                        'model_type' => 'Payment',
                        'model_id' => $payment->id,
                    ]);
                } else {
                    foreach ($payment->allocations as $allocation) {
                        $item = $allocation->invoiceItem;
                        if (!$item) {
                            continue; // Skip orphaned allocations (e.g. invoice item soft-deleted)
                        }
                        // Only show allocations that apply to this student's invoices
                        if (!$item->invoice_id || !$studentInvoiceIds->has($item->invoice_id)) {
                            continue;
                        }
                        if (($item->source ?? null) === 'swimming_attendance') {
                            continue; // Exclude allocations to daily-attendance swimming from statement
                        }
                        $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                        $detailedTransactions->push([
                            'date' => $payment->payment_date,
                            'type' => 'Payment',
                            'description' => 'Payment - ' . $voteheadName,
                            'reference' => $payment->receipt_number,
                            'votehead' => $voteheadName,
                            'debit' => 0,
                            'credit' => $allocation->amount,
                            'payment_id' => $payment->id,
                            'invoice_item_id' => $item->id,
                            'model_type' => 'PaymentAllocation',
                            'model_id' => $allocation->id,
                        ]);
                    }
                }
            }
        }
        
        // 3. Fee concessions (discounts) are already shown as part of invoice items above
        // No need to show them separately here to avoid duplication
        
        // 4. Add credit notes as separate line items
        foreach ($creditNotes as $note) {
            $item = $note->invoiceItem;
            if (!$item) {
                continue; // Skip orphaned credit notes (e.g. invoice item soft-deleted)
            }
            $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
            $amountLabel = 'Ksh ' . number_format($note->amount, 2);
            
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Credit Note',
                'description' => 'Credit Note - ' . $voteheadName . ': ' . ($note->reason ?? 'Adjustment') . " ({$amountLabel} noted)",
                'reference' => $note->credit_note_number ?? 'CN-' . $note->id,
                'votehead' => $voteheadName,
                'debit' => 0,
                'credit' => 0,
                'invoice_id' => $note->invoice_id,
                'invoice_item_id' => $note->invoice_item_id,
                'model_type' => 'CreditNote',
                'model_id' => $note->id,
            ]);
        }
        
        // 5. Add debit notes as separate line items
        foreach ($debitNotes as $note) {
            $item = $note->invoiceItem;
            if (!$item) {
                continue; // Skip orphaned debit notes (e.g. invoice item soft-deleted)
            }
            $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
            $amountLabel = 'Ksh ' . number_format($note->amount, 2);
            
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Debit Note',
                'description' => 'Debit Note - ' . $voteheadName . ': ' . ($note->reason ?? 'Adjustment') . " ({$amountLabel} noted)",
                'reference' => $note->debit_note_number ?? 'DN-' . $note->id,
                'votehead' => $voteheadName,
                'debit' => 0,
                'credit' => 0,
                'invoice_id' => $note->invoice_id,
                'invoice_item_id' => $note->invoice_item_id,
                'model_type' => 'DebitNote',
                'model_id' => $note->id,
            ]);
        }
        
        // 6. Add posting run reversals
        // Get invoice items that were created by posting runs that were later reversed
        // Items with posting_run_id pointing to a reversed run
        $reversedPostingRunsQuery = \App\Models\FeePostingRun::whereHas('invoiceItems', function($q) use ($student, $year) {
            $q->whereHas('invoice', function($q2) use ($student, $year) {
                $q2->where('student_id', $student->id)
                   ->where('year', $year);
            });
        })
        ->where(function($q) {
            $q->where('is_active', false)
              ->orWhereNotNull('reversed_at');
        });
        
        if ($term) {
            $reversedPostingRunsQuery->whereHas('term', function($q) use ($term) {
                $q->where('name', 'like', "%Term {$term}%")
                  ->orWhere('id', $term);
            });
        }
        
        $reversedRuns = $reversedPostingRunsQuery->get();
        
        foreach ($reversedRuns as $run) {
            // Get invoice items that belong to this reversed posting run
            $reversedItems = \App\Models\InvoiceItem::where('posting_run_id', $run->id)
                ->whereHas('invoice', function($q) use ($student) {
                    $q->where('student_id', $student->id);
                })
                ->with(['invoice', 'votehead'])
                ->get();
            
            foreach ($reversedItems as $item) {
                $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                $itemAmount = $item->amount ?? 0;
                $discountAmount = $item->discount_amount ?? 0;
                $netAmount = $itemAmount - $discountAmount;
                
                if ($netAmount > 0) {
                    $reversalDate = $run->reversed_at ?? $run->updated_at ?? $run->created_at;
                    $detailedTransactions->push([
                        'date' => $reversalDate,
                        'type' => 'Posting Reversal',
                        'description' => 'Fee Posting Reversed - ' . $voteheadName . ' (Run #' . $run->id . ')',
                        'reference' => 'RUN-' . $run->id . '-REV',
                        'votehead' => $voteheadName,
                        'debit' => 0,
                        'credit' => $netAmount, // Reversal credits (reduces balance)
                        'invoice_id' => $item->invoice_id,
                        'invoice_item_id' => $item->id,
                        'model_type' => 'FeePostingRun',
                        'model_id' => $run->id,
                        'is_reversal' => true,
                    ]);
                }
            }
        }
        
        // Sort all transactions by date
        $detailedTransactions = $detailedTransactions->sortBy('date');
        
        // Calculate totals: exclude daily-attendance swimming (source=swimming_attendance) and payments marked as swimming.
        // Total charges = sum of invoice item amounts for items with source != swimming_attendance
        $totalCharges = $invoices->sum(function($inv) {
            return $inv->items->filter(fn($i) => ($i->source ?? null) !== 'swimming_attendance')->sum('amount');
        });

        // Total discounts = invoice-level + item-level discounts, item-level restricted to non–swimming_attendance items
        $totalDiscounts = $invoices->sum('discount_amount') + $invoices->sum(function($inv) {
            return $inv->items->filter(fn($i) => ($i->source ?? null) !== 'swimming_attendance')->sum('discount_amount');
        });

        // Total payments = sum of allocations to displayed invoices where item has source != swimming_attendance and payment is not a swimming payment
        $invoiceIds = $invoices->pluck('id')->toArray();
        $totalPayments = 0;
        if (!empty($invoiceIds)) {
            $totalPayments = (float) PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoiceIds) {
                $q->whereIn('invoice_id', $invoiceIds);
                $q->where(function($q2) {
                    $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
                });
            })->whereHas('payment', function($q) use ($swimmingPaymentIds) {
                $q->where('reversed', false);
                if (!empty($swimmingPaymentIds)) {
                    $q->whereNotIn('id', $swimmingPaymentIds);
                }
            })->sum('amount');
        }
        $totalCreditNotes = 0;
        $totalDebitNotes = 0;
        
        // Balance: Charges - Discounts - Payments (credit/debit notes already reflected in item amounts)
        $invoiceBalance = $totalCharges - $totalDiscounts - $totalPayments;
        
        // For legacy years (pre-2026), calculate balance from legacy statement ending_balance
        // For 2026+, use invoice balance + balance brought forward
        if ($year < 2026) {
            // Get the ending_balance from the last term of this year in legacy data
            $lastLegacyTerm = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
                ->where('academic_year', $year)
                ->orderByDesc('term_number')
                ->first();
            
            // For legacy years, use the last term's ending balance for this year
            // Note: We can't easily map Term IDs to term_number for legacy statements,
            // so we always use the last term's ending balance regardless of term filter
            $balance = $lastLegacyTerm->ending_balance ?? 0;
            
            $balanceBroughtForward = 0; // Not applicable for legacy years
        } else {
            // For 2026+, include balance brought forward from legacy data
            $balanceBroughtForward = $hasBalanceBroughtForwardInInvoices
                ? 0
                : \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
            $balance = $invoiceBalance + $balanceBroughtForward;
        }
        
        // Get all terms and years for filter
        // Get years from invoices, legacy data, and academic years table
        $invoiceYears = Invoice::where('student_id', $student->id)
            ->distinct()
            ->pluck('year');
        
        $legacyYears = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
            ->distinct()
            ->pluck('academic_year');
        
        // Also include all academic years from the academic_years table
        $academicYears = \App\Models\AcademicYear::distinct()
            ->pluck('year');
        
        $years = $invoiceYears->merge($legacyYears)
            ->merge($academicYears)
            ->unique()
            ->sort()
            ->reverse()
            ->values();
        
        // Get terms - for legacy years (e.g. 2024) use LegacyStatementTerm; else use Term model
        $terms = collect();
        if ($year) {
            if ($year < 2026) {
                // Legacy: terms from LegacyStatementTerm for this student and year
                $legacyTerms = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
                    ->where('academic_year', $year)
                    ->orderBy('term_number')
                    ->get()
                    ->unique('term_number')
                    ->values();
                $terms = $legacyTerms->map(fn ($t) => (object) [
                    'id' => 'legacy-' . $t->term_number,
                    'name' => $t->term_name ?: ('Term ' . $t->term_number),
                ]);
            } else {
                $academicYear = \App\Models\AcademicYear::where('year', $year)->first();
                $academicYearId = $academicYear->id ?? null;
                $termsQuery = \App\Models\Term::query();
                if ($academicYearId) {
                    $termsQuery->where('academic_year_id', $academicYearId);
                }
                $terms = $termsQuery->orderBy('name')->get();
            }
        }
        
        // If AJAX request for terms only
        if ($request->ajax() && $request->get('get_terms')) {
            $termList = $terms->map(function ($t) {
                $id = is_object($t) ? $t->id : $t['id'];
                $name = is_object($t) ? $t->name : $t['name'];
                return ['id' => $id, 'name' => $name];
            });
            return response()->json(['terms' => $termList->values()]);
        }
        
        $comparisonPreviewId = $request->get('comparison_preview_id');
        $voteheads = Votehead::where('is_active', true)->orderBy('name')->get();

        return view('finance.student_statements.show', compact(
            'student',
            'invoices',
            'payments',
            'discounts',
            'creditNotes',
            'debitNotes',
            'totalCharges',
            'totalDiscounts',
            'totalPayments',
            'totalCreditNotes',
            'totalDebitNotes',
            'balance',
            'balanceBroughtForward',
            'invoiceBalance',
            'year',
            'term',
            'terms',
            'years',
            'detailedTransactions',
            'hasBalanceBroughtForwardInInvoices',
            'comparisonPreviewId',
            'voteheads'
        ));
    }

    public function updateLegacyLine(Request $request, Student $student, LegacyStatementLine $line)
    {
        if (!$line->term || (int) $line->term->student_id !== (int) $student->id) {
            abort(404);
        }

        $validated = $request->validate([
            'txn_date' => 'nullable|date',
            'narration_raw' => 'required|string',
            'reference_number' => 'required|string|max:255',
            'votehead' => 'required|string|max:255',
            'amount_dr' => 'nullable|numeric|min:0',
            'amount_cr' => 'nullable|numeric|min:0',
            'confidence' => 'nullable|in:high,draft',
        ]);

        if (!empty($validated['amount_dr']) && !empty($validated['amount_cr'])) {
            return back()->withErrors(['amount_cr' => 'Dr and Cr cannot both be set.'])->withInput();
        }

        $beforeValues = [
            'txn_date' => $line->txn_date?->toDateString(),
            'narration_raw' => $line->narration_raw,
            'amount_dr' => $line->amount_dr,
            'amount_cr' => $line->amount_cr,
            'running_balance' => $line->running_balance,
            'confidence' => $line->confidence,
            'reference_number' => $line->reference_number,
            'txn_code' => $line->txn_code,
            'votehead' => $line->votehead,
        ];

        $narration = $validated['narration_raw'];
        $reference = $validated['reference_number'];
        $txnCode = $line->txn_code;
        if ($narration !== $line->narration_raw) {
            $service = app(\App\Services\LegacyFinanceImportService::class);
            $reference = $service->extractReference($narration) ?: $reference;
            $txnCode = $service->extractTxnCode($narration) ?: $txnCode;
        }

        $line->update([
            'txn_date' => $validated['txn_date'] ?? null,
            'narration_raw' => $validated['narration_raw'],
            'amount_dr' => $validated['amount_dr'] ?? null,
            'amount_cr' => $validated['amount_cr'] ?? null,
            'confidence' => $validated['confidence'] ?? 'high',
            'reference_number' => $reference,
            'txn_code' => $txnCode,
            'votehead' => $validated['votehead'],
        ]);

        app(LegacyStatementRecalcService::class)->recalcFromLine($line);
        $line->refresh();

        $afterValues = [
            'txn_date' => $line->txn_date?->toDateString(),
            'narration_raw' => $line->narration_raw,
            'amount_dr' => $line->amount_dr,
            'amount_cr' => $line->amount_cr,
            'running_balance' => $line->running_balance,
            'confidence' => $line->confidence,
            'reference_number' => $line->reference_number,
            'txn_code' => $line->txn_code,
            'votehead' => $line->votehead,
        ];

        $changedFields = [];
        foreach ($beforeValues as $field => $beforeValue) {
            $afterValue = $afterValues[$field] ?? null;
            if ($beforeValue != $afterValue) {
                $changedFields[] = $field;
            }
        }

        if (!empty($changedFields)) {
            LegacyStatementLineEditHistory::create([
                'line_id' => $line->id,
                'batch_id' => $line->batch_id,
                'edited_by' => auth()->id(),
                'before_values' => $beforeValues,
                'after_values' => $afterValues,
                'changed_fields' => $changedFields,
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Line updated successfully',
                'running_balance' => $line->running_balance,
            ]);
        }

        return back()->with('success', 'Line updated.');
    }

    public function storeEntry(Request $request, Student $student)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'term' => 'required|string',
            'entry_date' => 'required|date',
            'entry_type' => 'required|in:debit,credit',
            'description' => 'required|string',
            'reference' => 'required|string|max:255',
            'votehead_id' => 'required|exists:voteheads,id',
            'amount' => 'required|numeric|min:0.01',
            'invoice_id' => 'nullable|exists:invoices,id',
            'invoice_item_id' => 'required_if:entry_type,credit|exists:invoice_items,id',
        ]);

        $termNumber = $this->resolveTermNumber($validated['term']);
        if (!$termNumber) {
            return back()->withErrors(['term' => 'Please select a valid term.'])->withInput();
        }

        if ($validated['entry_type'] === 'debit') {
            $invoice = null;
            if (!empty($validated['invoice_id'])) {
                $invoice = Invoice::find($validated['invoice_id']);
                if (!$invoice || (int) $invoice->student_id !== (int) $student->id) {
                    return back()->withErrors(['invoice_id' => 'Invalid invoice for this student.'])->withInput();
                }
            } else {
                $invoice = InvoiceService::ensure($student->id, (int) $validated['year'], $termNumber);
            }

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $validated['votehead_id'],
                'amount' => $validated['amount'],
                'discount_amount' => 0,
                'original_amount' => $validated['amount'],
                'status' => 'active',
                'source' => 'statement_adjustment',
                'effective_date' => $validated['entry_date'],
                'posted_at' => now(),
            ]);

            InvoiceService::recalc($invoice);

            \App\Services\InvoiceService::allocateUnallocatedPaymentsForStudent($invoice->student_id);

            return back()->with('success', 'Debit entry added successfully.');
        }

        $invoiceItem = InvoiceItem::with('invoice')->find($validated['invoice_item_id']);
        if (!$invoiceItem || !$invoiceItem->invoice || (int) $invoiceItem->invoice->student_id !== (int) $student->id) {
            return back()->withErrors(['invoice_item_id' => 'Invalid invoice item for this student.'])->withInput();
        }

        CreditNote::create([
            'invoice_id' => $invoiceItem->invoice_id,
            'invoice_item_id' => $invoiceItem->id,
            'amount' => $validated['amount'],
            'reason' => $validated['description'],
            'notes' => $validated['reference'],
            'issued_at' => $validated['entry_date'],
            'issued_by' => auth()->id(),
        ]);

        InvoiceService::recalc($invoiceItem->invoice);

        \App\Services\InvoiceService::allocateUnallocatedPaymentsForStudent($invoiceItem->invoice->student_id);

        return back()->with('success', 'Credit entry added successfully.');
    }

    private function resolveTermNumber(string $termValue): ?int
    {
        if (str_starts_with($termValue, 'legacy-')) {
            $termNumber = (int) str_replace('legacy-', '', $termValue);
            return $termNumber > 0 ? $termNumber : null;
        }

        if (is_numeric($termValue)) {
            $termModel = \App\Models\Term::find($termValue);
            if ($termModel && preg_match('/Term\s*(\d+)/i', $termModel->name ?? '', $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    public function print(Request $request, Student $student)
    {
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        
        // Same year scope as show(): by year column or academic_year relation
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where(function ($q) use ($year) {
                $q->where('year', $year)
                  ->orWhereHas('academicYear', function ($q2) use ($year) {
                      $q2->where('year', $year);
                  });
            })
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            })
            ->with([
                'items.votehead', 
                'items.creditNotes', 
                'items.debitNotes',
                'items.allocations.payment',
                'term', 
                'academicYear'
            ]);
            
        if ($term) {
            $invoicesQuery->whereHas('term', function($q) use ($term) {
                $q->where('name', 'like', "%Term {$term}%")
                  ->orWhere('id', $term);
            });
        }
        
        $invoices = $invoicesQuery->orderBy('created_at')->get();

        // Get all payments with allocations. For 2026+ with term: include any payment that has
        // at least one allocation to the displayed invoices (so the transaction list matches Total Payments).
        $printInvoiceIds = $invoices->pluck('id')->toArray();
        $paymentsQuery = Payment::where('student_id', $student->id)
            ->with(['allocations.invoiceItem.votehead', 'allocations.invoiceItem.invoice', 'paymentMethod']);

        if ($year >= 2026) {
            if (!empty($printInvoiceIds)) {
                $paymentsQuery->whereHas('allocations', function ($q) use ($printInvoiceIds) {
                    $q->whereHas('invoiceItem', function ($q2) use ($printInvoiceIds) {
                        $q2->whereIn('invoice_id', $printInvoiceIds);
                    });
                });
            } else {
                $paymentsQuery->whereRaw('1 = 0');
            }
        } else {
            $paymentsQuery->whereYear('payment_date', $year);
            if ($term) {
                $paymentsQuery->where(function ($q) use ($term) {
                    $q->whereHas('invoice', function ($q2) use ($term) {
                        $q2->whereHas('term', function ($q3) use ($term) {
                            $q3->where('name', 'like', "%Term {$term}%")
                               ->orWhere('id', $term);
                        });
                    })->orWhereNull('invoice_id');
                });
            }
        }

        $payments = $paymentsQuery->orderBy('payment_date')->get();

        // Exclude only daily-attendance swimming (source=swimming_attendance). Term swimming + their credit/debit notes are included.
        $excludeSourceSwimmingAttendance = function ($q) {
            $q->where(function ($q2) {
                $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
            });
        };
        $swimmingPaymentIds = $this->getSwimmingPaymentIdsForStatement();

        $creditNotesQuery = CreditNote::whereHas('invoiceItem', function($q) use ($student, $excludeSourceSwimmingAttendance) {
            $q->whereHas('invoice', function($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
            $excludeSourceSwimmingAttendance($q);
        })->whereYear('created_at', $year)->with(['invoiceItem.invoice', 'invoiceItem.votehead']);
        if ($term) {
            $creditNotesQuery->whereHas('invoiceItem.invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")->orWhere('id', $term);
                });
            });
        }
        $creditNotes = $creditNotesQuery->orderBy('created_at')->get();

        $debitNotesQuery = DebitNote::whereHas('invoiceItem', function($q) use ($student, $excludeSourceSwimmingAttendance) {
            $q->whereHas('invoice', function($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
            $excludeSourceSwimmingAttendance($q);
        })->whereYear('created_at', $year)->with(['invoiceItem.invoice', 'invoiceItem.votehead']);
        if ($term) {
            $debitNotesQuery->whereHas('invoiceItem.invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")->orWhere('id', $term);
                });
            });
        }
        $debitNotes = $debitNotesQuery->orderBy('created_at')->get();

        $discounts = FeeConcession::where('student_id', $student->id)
            ->where('year', $year)
            ->where('approval_status', 'approved')
            ->with('discountTemplate')
            ->orderBy('created_at')
            ->get();
        
        // Recalculate all invoices
        foreach ($invoices as $invoice) {
            $invoice->recalculate();
        }
        
        // Legacy: only for years < 2026; show all legacy entries (no term filter)
        $legacyLines = collect();
        if ($year < 2026) {
            $legacyLines = LegacyStatementLine::with('term')
                ->whereHas('term', function ($q) use ($student, $year) {
                    $q->where('student_id', $student->id)
                      ->where('academic_year', '<', 2026)
                      ->where('academic_year', $year);
                })
                ->orderBy('txn_date')
                ->orderBy('sequence_no')
                ->get();
        }

        $legacyTransactions = collect();
        foreach ($legacyLines as $line) {
            $legacyTransactions->push([
                'date' => $line->txn_date ?? $line->created_at,
                'type' => 'Legacy',
                'narration' => $line->narration_raw,
                'reference' => $line->reference_number ?? $line->txn_code ?? 'Legacy',
                'votehead' => $line->votehead ?? 'Legacy',
                'term_name' => $line->term->term_name ?? '',
                'term_year' => $line->term->academic_year ?? $year,
                'grade' => $student->classroom->name ?? '',
                'debit' => (float) ($line->amount_dr ?? 0),
                'credit' => (float) ($line->amount_cr ?? 0),
            ]);
        }
        
        // Build detailed transactions - same exclusions as show: exclude daily-attendance swimming and payments marked as swimming
        $detailedTransactions = collect()->merge($legacyTransactions);

        $hasBalanceBroughtForwardInInvoicesPrint = $invoices->flatMap->items->contains(function ($item) {
            $voteheadCode = $item->votehead->code ?? null;
            return ($item->source ?? null) === 'balance_brought_forward' || $voteheadCode === 'BAL_BF';
        });
        if ($year >= 2026 && !$hasBalanceBroughtForwardInInvoicesPrint) {
            $legacyBbfPrint = \App\Models\LegacyStatementTerm::getBalanceBroughtForward($student);
            if ($legacyBbfPrint !== null && abs((float) $legacyBbfPrint) >= 0.01) {
                $bfDatePrint = \Carbon\Carbon::createFromDate((int) $year, 1, 1)->startOfDay();
                if ((float) $legacyBbfPrint > 0) {
                    $detailedTransactions->prepend([
                        'date' => $bfDatePrint,
                        'type' => 'Balance Brought Forward',
                        'narration' => 'Balance brought forward from ' . ($year - 1),
                        'reference' => 'BBF-' . ($year - 1),
                        'votehead' => 'Balance Brought Forward',
                        'term_name' => '',
                        'term_year' => $year,
                        'grade' => $student->classroom->name ?? '',
                        'debit' => (float) $legacyBbfPrint,
                        'credit' => 0,
                    ]);
                } else {
                    $detailedTransactions->prepend([
                        'date' => $bfDatePrint,
                        'type' => 'Balance Brought Forward',
                        'narration' => 'Overpayment brought forward from ' . ($year - 1),
                        'reference' => 'BBF-' . ($year - 1),
                        'votehead' => 'Balance Brought Forward',
                        'term_name' => '',
                        'term_year' => $year,
                        'grade' => $student->classroom->name ?? '',
                        'debit' => 0,
                        'credit' => abs((float) $legacyBbfPrint),
                    ]);
                }
            }
        }

        $swimmingPaymentIdsSet = array_flip($swimmingPaymentIds);

        // Add invoice items (exclude source=swimming_attendance)
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                if (($item->source ?? null) === 'swimming_attendance') {
                    continue;
                }
                $itemDate = $item->posted_at ?? $item->effective_date ?? $item->created_at ?? $invoice->created_at;
                $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                $itemAmount = $item->amount ?? 0;
                $discountAmount = $item->discount_amount ?? 0;
                
                if ($itemAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Invoice',
                        'narration' => $voteheadName . ' - ' . ($invoice->invoice_number ?? 'N/A'),
                        'reference' => $invoice->invoice_number ?? 'N/A',
                        'votehead' => $voteheadName,
                        'term_name' => $invoice->term->name ?? '',
                        'term_year' => $year,
                        'grade' => $student->currentClass->name ?? $student->classroom->name ?? '',
                        'debit' => $itemAmount,
                        'credit' => 0,
                    ]);
                } elseif ($itemAmount < 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Balance Brought Forward',
                        'narration' => 'Overpayment - ' . $voteheadName,
                        'reference' => $invoice->invoice_number ?? 'N/A',
                        'votehead' => $voteheadName,
                        'term_name' => $invoice->term->name ?? '',
                        'term_year' => $year,
                        'grade' => $student->currentClass->name ?? $student->classroom->name ?? '',
                        'debit' => 0,
                        'credit' => abs($itemAmount),
                    ]);
                }
                
                if ($discountAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Discount',
                        'narration' => 'DISCOUNT - ' . $voteheadName,
                        'reference' => $invoice->invoice_number ?? 'N/A',
                        'votehead' => $voteheadName,
                        'term_name' => $invoice->term->name ?? '',
                        'term_year' => $year,
                        'grade' => $student->currentClass->name ?? $student->classroom->name ?? '',
                        'debit' => 0,
                        'credit' => $discountAmount,
                    ]);
                }
            }
        }

        // Add payments (exclude payments marked as swimming and allocations to swimming_attendance items)
        $printStudentInvoiceIds = $invoices->pluck('id')->flip();
        foreach ($payments as $payment) {
            if (isset($swimmingPaymentIdsSet[$payment->id])) {
                continue;
            }
            if (!$payment->reversed) {
                if ($payment->allocations->isEmpty()) {
                    $detailedTransactions->push([
                        'date' => $payment->payment_date,
                        'type' => 'Payment',
                        'narration' => 'RECEIPT - ' . ($payment->receipt_number ?? 'N/A') . ' - ' . ($payment->paymentMethod->name ?? 'CASH'),
                        'reference' => $payment->receipt_number ?? 'N/A',
                        'votehead' => 'Unallocated',
                        'term_name' => '',
                        'term_year' => $year,
                        'grade' => $student->currentClass->name ?? $student->classroom->name ?? '',
                        'debit' => 0,
                        'credit' => $payment->amount,
                    ]);
                } else {
                    foreach ($payment->allocations as $allocation) {
                        $item = $allocation->invoiceItem;
                        if (!$item || ($item->source ?? null) === 'swimming_attendance') {
                            continue;
                        }
                        // Only include allocations that apply to this student's invoices
                        if (!$item->invoice_id || !$printStudentInvoiceIds->has($item->invoice_id)) {
                            continue;
                        }
                        $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                        $detailedTransactions->push([
                            'date' => $payment->payment_date,
                            'type' => 'Payment',
                            'narration' => 'RECEIPT - ' . ($payment->receipt_number ?? 'N/A') . ' - ' . ($payment->paymentMethod->name ?? 'CASH'),
                            'reference' => $payment->receipt_number ?? 'N/A',
                            'votehead' => $voteheadName,
                            'term_year' => $year,
                            'grade' => $student->currentClass->name ?? $student->classroom->name ?? '',
                            'debit' => 0,
                            'credit' => $allocation->amount,
                        ]);
                    }
                }
            }
        }
        
        // Add credit notes
        foreach ($creditNotes as $note) {
            $item = $note->invoiceItem;
            if (!$item) {
                continue;
            }
            $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
            $amountLabel = 'Ksh ' . number_format($note->amount, 2);
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Credit Note',
                'narration' => 'CREDIT NOTE - ' . $voteheadName . ' - ' . ($note->credit_note_number ?? 'CN-' . $note->id) . " ({$amountLabel} noted)",
                'reference' => $note->credit_note_number ?? 'CN-' . $note->id,
                'votehead' => $voteheadName,
                'term_year' => $year,
                'grade' => $student->currentClass->name ?? '',
                'debit' => 0,
                'credit' => 0,
            ]);
        }
        
        // Add debit notes
        foreach ($debitNotes as $note) {
            $item = $note->invoiceItem;
            if (!$item) {
                continue;
            }
            $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
            $amountLabel = 'Ksh ' . number_format($note->amount, 2);
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Debit Note',
                'narration' => 'DEBIT NOTE - ' . $voteheadName . ' - ' . ($note->debit_note_number ?? 'DN-' . $note->id) . " ({$amountLabel} noted)",
                'reference' => $note->debit_note_number ?? 'DN-' . $note->id,
                'votehead' => $voteheadName,
                'term_year' => $year,
                'grade' => $student->currentClass->name ?? '',
                'debit' => 0,
                'credit' => 0,
            ]);
        }
        
        // Sort by date
        $detailedTransactions = $detailedTransactions->sortBy('date')->values();
        
        // Calculate totals
        $totalDebit = $detailedTransactions->sum('debit');
        $totalCredit = $detailedTransactions->sum('credit');
        
        // Calculate balance properly
        $balanceBroughtForward = 0;
        $hasBalanceBroughtForwardInInvoices = false;
        // For legacy years (pre-2026), use ending_balance from last term
        // For 2026+, use invoice balance + balance brought forward
        if ($year < 2026) {
            $lastLegacyTerm = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
                ->where('academic_year', $year)
                ->orderByDesc('term_number')
                ->first();
            $finalBalance = $lastLegacyTerm->ending_balance ?? 0;
        } else {
            // Same exclusion rules as show: exclude daily-attendance swimming and payments marked as swimming
            $totalCharges = $invoices->sum(function($inv) {
                return $inv->items->filter(fn($i) => ($i->source ?? null) !== 'swimming_attendance')->sum('amount');
            });
            $totalDiscounts = $invoices->sum('discount_amount') + $invoices->sum(function($inv) {
                return $inv->items->filter(fn($i) => ($i->source ?? null) !== 'swimming_attendance')->sum('discount_amount');
            });
            $invoiceIds = $invoices->pluck('id')->toArray();
            $totalPayments = 0;
            if (!empty($invoiceIds)) {
                $totalPayments = (float) PaymentAllocation::whereHas('invoiceItem', function($q) use ($invoiceIds) {
                    $q->whereIn('invoice_id', $invoiceIds);
                    $q->where(function($q2) {
                        $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
                    });
                })->whereHas('payment', function($q) use ($swimmingPaymentIds) {
                    $q->where('reversed', false);
                    if (!empty($swimmingPaymentIds)) {
                        $q->whereNotIn('id', $swimmingPaymentIds);
                    }
                })->sum('amount');
            }
            $totalCreditNotes = 0;
            $totalDebitNotes = 0;
            $invoiceBalance = $totalCharges - $totalDiscounts - $totalPayments;
            $hasBalanceBroughtForwardInInvoices = $invoices->flatMap->items->contains(function ($item) {
                $voteheadCode = $item->votehead->code ?? null;
                return ($item->source ?? null) === 'balance_brought_forward' || $voteheadCode === 'BAL_BF';
            });
            $balanceBroughtForward = $hasBalanceBroughtForwardInInvoices
                ? 0
                : \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
            $finalBalance = $invoiceBalance + $balanceBroughtForward;
        }
        
        // Get terms for display
        $terms = \App\Models\Term::orderBy('name')->get();
        $branding = $this->branding();
        $statementHeader = \App\Models\Setting::get('statement_header', '');
        $statementFooter = \App\Models\Setting::get('statement_footer', '');
        
        return view('finance.student_statements.print', compact(
            'student',
            'detailedTransactions',
            'year',
            'term',
            'terms',
            'branding',
            'statementHeader',
            'statementFooter',
            'totalDebit',
            'totalCredit',
            'finalBalance',
            'balanceBroughtForward',
            'hasBalanceBroughtForwardInInvoices'
        ));
    }
    
    public function export(Request $request, Student $student)
    {
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        $format = $request->get('format', 'pdf'); // pdf or csv
        
        // Same year scope as show(): by year column or academic_year relation
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where(function ($q) use ($year) {
                $q->where('year', $year)
                  ->orWhereHas('academicYear', function ($q2) use ($year) {
                      $q2->where('year', $year);
                  });
            })
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            })
            ->with(['items.votehead', 'term', 'academicYear']);
            
        if ($term) {
            $invoicesQuery->whereHas('term', function($q) use ($term) {
                $q->where('name', 'like', "%Term {$term}%")
                  ->orWhere('id', $term);
            });
        }
        
        $invoices = $invoicesQuery->orderBy('created_at')->get();
        $payments = Payment::where('student_id', $student->id)
            ->whereYear('payment_date', $year)
            ->where('reversed', false)
            ->orderBy('payment_date')
            ->get();
        $discounts = FeeConcession::where('student_id', $student->id)
            ->where('year', $year)
            ->where('approval_status', 'approved')
            ->with('discountTemplate')
            ->orderBy('created_at')
            ->get();
        
        if ($format === 'csv') {
            return $this->exportCsv($student, $invoices, $payments, $discounts, $year, $term);
        } else {
            return $this->exportPdf($student, $invoices, $payments, $discounts, $year, $term);
        }
    }
    
    private function exportCsv($student, $invoices, $payments, $discounts, $year, $term)
    {
        $filename = "statement_{$student->admission_number}_{$year}" . ($term ? "_term{$term}" : '') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($student, $invoices, $payments, $discounts, $year, $term) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Student Fee Statement']);
            fputcsv($file, ['Student:', $student->full_name]);
            fputcsv($file, ['Admission Number:', $student->admission_number]);
            fputcsv($file, ['Class:', $student->currentClass->name ?? 'N/A']);
            fputcsv($file, ['Year:', $year]);
            if ($term) {
                fputcsv($file, ['Term:', "Term {$term}"]);
            }
            fputcsv($file, []);
            
            // Invoices
            fputcsv($file, ['INVOICES']);
            fputcsv($file, ['Date', 'Invoice #', 'Description', 'Amount']);
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->created_at->format('Y-m-d'),
                    $invoice->invoice_number,
                    "Invoice for " . ($invoice->term->name ?? 'Term') . " {$year}",
                    number_format($invoice->total, 2)
                ]);
            }
            fputcsv($file, []);
            
            // Payments
            fputcsv($file, ['PAYMENTS']);
            fputcsv($file, ['Date', 'Receipt #', 'Amount', 'Method']);
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_date->format('Y-m-d'),
                    $payment->receipt_number,
                    number_format($payment->amount, 2),
                    $payment->paymentMethod->name ?? 'N/A'
                ]);
            }
            fputcsv($file, []);
            
            // Discounts
            if ($discounts->count() > 0) {
                fputcsv($file, ['DISCOUNTS']);
                fputcsv($file, ['Date', 'Description', 'Amount']);
                foreach ($discounts as $discount) {
                    fputcsv($file, [
                        $discount->created_at->format('Y-m-d'),
                        $discount->discountTemplate->name ?? 'Discount',
                        number_format($discount->value, 2)
                    ]);
                }
                fputcsv($file, []);
            }
            
            // Summary (match on-screen totals: charges from invoices, payments = allocated to these invoices from non-reversed payments only)
            $totalCharges = $invoices->sum('total');
            $invoiceIds = $invoices->pluck('id');
            $totalPayments = $invoiceIds->isNotEmpty()
                ? PaymentAllocation::whereHas('invoiceItem', fn ($q) => $q->whereIn('invoice_id', $invoiceIds))
                    ->whereHas('payment', fn ($q) => $q->where('reversed', false))
                    ->sum('amount')
                : 0;
            $totalDiscounts = $discounts->sum('value');
            $balance = $totalCharges - $totalPayments - $totalDiscounts;
            
            fputcsv($file, ['SUMMARY']);
            fputcsv($file, ['Total Charges:', number_format($totalCharges, 2)]);
            fputcsv($file, ['Total Payments:', number_format($totalPayments, 2)]);
            fputcsv($file, ['Total Discounts:', number_format($totalDiscounts, 2)]);
            fputcsv($file, ['Balance:', number_format($balance, 2)]);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function exportPdf($student, $invoices, $payments, $discounts, $year, $term)
    {
        // Get terms for display
        $terms = \App\Models\Term::orderBy('name')->get();
        $branding = $this->branding();
        $statementHeader = \App\Models\Setting::get('statement_header', '');
        $statementFooter = \App\Models\Setting::get('statement_footer', '');
        $printedAt = now();
        $printedBy = optional(auth()->user())->name ?? 'System';
        
        // For PDF, we'll use the same view but with print styles
        return view('finance.student_statements.print', compact(
            'student',
            'invoices',
            'payments',
            'discounts',
            'year',
            'term',
            'terms',
            'branding',
            'statementHeader',
            'statementFooter',
            'printedAt',
            'printedBy'
        ));
    }

    /**
     * Public view of statement using hashed ID (no authentication required)
     * Note: This uses student's hashed_id, not statement's (statements are generated on-the-fly)
     */
    public function publicView(string $hash, Request $request)
    {
        // Find student by hashed_id (we'll need to add this to Student model)
        // For now, we'll use a different approach - generate a token for statements
        // Actually, statements are dynamic, so we'll use student's admission number as hash
        // Or better: create a statement token system
        
        // For simplicity, let's use student ID encoded in hash
        // In production, you'd want a proper token system
        $student = Student::where('admission_number', $hash)->first();
        
        if (!$student) {
            abort(404, 'Statement not found');
        }
        
        // Use the same logic as show method
        return $this->show($request, $student);
    }

    /**
     * Payment IDs that are "swimming" (from bank/M-PESA marked as swimming). Exclude from fee-statement totals and transaction list.
     */
    private function getSwimmingPaymentIdsForStatement(): array
    {
        $ids = collect();
        if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                \App\Models\BankStatementTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        if (Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                \App\Models\MpesaC2BTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        return $ids->unique()->filter()->values()->toArray();
    }

    private function branding(): array
    {
        $kv = \Illuminate\Support\Facades\DB::table('settings')->pluck('value', 'key')->map(fn($v) => trim((string)$v));

        $name    = $kv['school_name']    ?? config('app.name', 'Your School');
        $email   = $kv['school_email']   ?? '';
        $phone   = $kv['school_phone']   ?? '';
        $website = $kv['school_website'] ?? '';
        $address = $kv['school_address'] ?? '';

        // Try school_logo first (stored as filename in public/images/)
        // Then try school_logo_path (full path)
        $logoFilename = $kv['school_logo'] ?? null;
        $logoPathSetting = $kv['school_logo_path'] ?? null;
        
        $candidates = [];
        
        // If school_logo is set, check public/images/ first
        if ($logoFilename) {
            $candidates[] = public_path('images/' . $logoFilename);
        }
        
        // If school_logo_path is set, use it directly
        if ($logoPathSetting) {
            $candidates[] = public_path($logoPathSetting);
            $candidates[] = public_path('storage/' . $logoPathSetting);
            $candidates[] = storage_path('app/public/' . $logoPathSetting);
        }
        
        // Fallback to default
        if (empty($candidates)) {
            $candidates[] = public_path('images/logo.png');
        }

        $logoBase64 = null;
        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');

            if ($mime === 'image/png' && !extension_loaded('gd') && !extension_loaded('imagick')) {
                $logoBase64 = null;
                break;
            }

            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            break;
        }

        return compact('name', 'email', 'phone', 'website', 'address', 'logoBase64');
    }
}


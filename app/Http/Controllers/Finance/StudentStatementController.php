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
use Illuminate\Http\Request;
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
        
        // Get all invoices for the student with detailed items (exclude reversed)
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where('year', $year)
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
        
        // Get all payments with allocations
        $paymentsQuery = Payment::where('student_id', $student->id)
            ->whereYear('payment_date', $year)
            ->with(['allocations.invoiceItem.votehead', 'allocations.invoiceItem.invoice']);
            
        if ($term) {
            $paymentsQuery->whereHas('invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")
                       ->orWhere('id', $term);
                });
            })->orWhereNull('invoice_id');
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
        
        // Get all credit notes
        $creditNotesQuery = CreditNote::whereHas('invoiceItem', function($q) use ($student) {
            $q->whereHas('invoice', function($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
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
        
        // Get all debit notes
        $debitNotesQuery = DebitNote::whereHas('invoiceItem', function($q) use ($student) {
            $q->whereHas('invoice', function($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
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
        
        // Recalculate all invoices to ensure accurate balances
        foreach ($invoices as $invoice) {
            $invoice->recalculate();
        }
        
        // Legacy transactions (read-only, as parsed) for historical years (pre-2026, e.g. 2024)
        $legacyLinesQuery = LegacyStatementLine::with('term')
            ->whereHas('term', function($q) use ($student, $year) {
                $q->where('student_id', $student->id)
                  ->where('academic_year', '<', 2026);
                
                // Filter by year if specified
                if ($year < 2026) {
                    $q->where('academic_year', $year);
                }
            });
        
        // Filter by term if specified and year is legacy (2024 or pre-2026)
        $legacyTermNumber = null;
        if ($term && $year < 2026) {
            if (is_string($term) && str_starts_with($term, 'legacy-')) {
                $legacyTermNumber = (int) substr($term, 7);
            } else {
                $termModel = \App\Models\Term::find($term);
                if ($termModel && preg_match('/term\s*(\d+)/i', $termModel->name ?? '', $matches)) {
                    $legacyTermNumber = (int) $matches[1];
                }
            }
            if ($legacyTermNumber) {
                $legacyLinesQuery->whereHas('term', function ($q) use ($year, $legacyTermNumber) {
                    $q->where('academic_year', $year)
                      ->where('term_number', $legacyTermNumber);
                });
            }
        }
        
        $legacyLines = $legacyLinesQuery
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
        
        // 1. Add invoice items (each votehead as separate line item)
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $itemDate = $item->posted_at ?? $item->effective_date ?? $item->created_at ?? $invoice->created_at;
                $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                $itemAmount = $item->amount ?? 0;
                $discountAmount = $item->discount_amount ?? 0;
                $netAmount = $itemAmount - $discountAmount;
                
                if ($netAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Invoice Item',
                        'description' => $voteheadName . ' - ' . ($invoice->term->name ?? 'Term') . ' ' . $year,
                        'reference' => $invoice->invoice_number,
                        'votehead' => $voteheadName,
                        'debit' => $netAmount,
                        'credit' => 0,
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
        
        // 2. Add payment allocations (each votehead payment as separate line item)
        foreach ($payments as $payment) {
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
            
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Credit Note',
                'description' => 'Credit Note - ' . $voteheadName . ': ' . ($note->reason ?? 'Adjustment'),
                'reference' => $note->credit_note_number ?? 'CN-' . $note->id,
                'votehead' => $voteheadName,
                'debit' => 0,
                'credit' => $note->amount,
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
            
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Debit Note',
                'description' => 'Debit Note - ' . $voteheadName . ': ' . ($note->reason ?? 'Adjustment'),
                'reference' => $note->debit_note_number ?? 'DN-' . $note->id,
                'votehead' => $voteheadName,
                'debit' => $note->amount,
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
        
        // Calculate totals from the invoices we are displaying (student + year + term)
        // Total charges = sum of invoice item amounts (gross, before discounts)
        $totalCharges = $invoices->sum(function($inv) {
            return $inv->items->sum('amount') ?? 0;
        });
        
        // Total discounts = sum of invoice-level and item-level discounts
        $totalDiscounts = $invoices->sum('discount_amount') + $invoices->sum(function($inv) {
            return $inv->items->sum('discount_amount') ?? 0;
        });
        
        // Total payments = sum of amounts *allocated to these invoices* (not full payment.amount:
        // one payment can be split across students/invoices; we must only count the portion applied here)
        $totalPayments = $invoices->sum('paid_amount');
        $totalCreditNotes = $creditNotes->sum('amount');
        $totalDebitNotes = $debitNotes->sum('amount');
        
        // Balance: Charges - Discounts - Payments + Debit Notes - Credit Notes
        $invoiceBalance = $totalCharges - $totalDiscounts - $totalPayments + $totalDebitNotes - $totalCreditNotes;
        
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
            $balanceBroughtForward = \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
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
            'comparisonPreviewId'
        ));
    }

    public function print(Request $request, Student $student)
    {
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        
        // Get all invoices for the student with detailed items (exclude reversed)
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where('year', $year)
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
        
        // Get all payments with allocations
        $paymentsQuery = Payment::where('student_id', $student->id)
            ->whereYear('payment_date', $year)
            ->with(['allocations.invoiceItem.votehead', 'allocations.invoiceItem.invoice', 'paymentMethod']);
            
        if ($term) {
            $paymentsQuery->whereHas('invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")
                       ->orWhere('id', $term);
                });
            })->orWhereNull('invoice_id');
        }
        
        $payments = $paymentsQuery->orderBy('payment_date')->get();
        
        $creditNotes = CreditNote::whereHas('invoiceItem.invoice', function($q) use ($student, $year) {
            $q->where('student_id', $student->id)->where('year', $year);
        })->with(['invoiceItem.votehead', 'invoiceItem.invoice'])->orderBy('created_at')->get();
        
        $debitNotes = DebitNote::whereHas('invoiceItem.invoice', function($q) use ($student, $year) {
            $q->where('student_id', $student->id)->where('year', $year);
        })->with(['invoiceItem.votehead', 'invoiceItem.invoice'])->orderBy('created_at')->get();
        
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
        
        // Get legacy transactions (read-only, as parsed) for historical years (pre-2026, e.g. 2024)
        $legacyLinesQuery = LegacyStatementLine::with('term')
            ->whereHas('term', function($q) use ($student, $year) {
                $q->where('student_id', $student->id)
                  ->where('academic_year', '<', 2026);
                
                // Filter by year if specified
                if ($year < 2026) {
                    $q->where('academic_year', $year);
                }
            });
        
        // Filter by term if specified and year is legacy (2024 or pre-2026)
        $legacyTermNumber = null;
        if ($term && $year < 2026) {
            if (is_string($term) && str_starts_with($term, 'legacy-')) {
                $legacyTermNumber = (int) substr($term, 7);
            } else {
                $termModel = \App\Models\Term::find($term);
                if ($termModel && preg_match('/term\s*(\d+)/i', $termModel->name ?? '', $matches)) {
                    $legacyTermNumber = (int) $matches[1];
                }
            }
            if ($legacyTermNumber) {
                $legacyLinesQuery->whereHas('term', function ($q) use ($year, $legacyTermNumber) {
                    $q->where('academic_year', $year)
                      ->where('term_number', $legacyTermNumber);
                });
            }
        }
        
        $legacyLines = $legacyLinesQuery
            ->orderBy('txn_date')
            ->orderBy('sequence_no')
            ->get();

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
        
        // Build detailed transactions - reuse logic from show method
        $detailedTransactions = collect()->merge($legacyTransactions);
        
        // Add invoice items
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $itemDate = $item->posted_at ?? $item->effective_date ?? $item->created_at ?? $invoice->created_at;
                $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                $itemAmount = $item->amount ?? 0;
                $discountAmount = $item->discount_amount ?? 0;
                $netAmount = $itemAmount - $discountAmount;
                
                if ($netAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Invoice',
                        'narration' => $voteheadName . ' - ' . ($invoice->invoice_number ?? 'N/A'),
                        'reference' => $invoice->invoice_number ?? 'N/A',
                        'votehead' => $voteheadName,
                        'term_name' => $invoice->term->name ?? '',
                        'term_name' => '',
                        'term_year' => $year,
                        'grade' => $student->currentClass->name ?? '',
                        'debit' => $netAmount,
                        'credit' => 0,
                    ]);
                }
                
                // Add item-level discounts
                if ($discountAmount > 0) {
                    $detailedTransactions->push([
                        'date' => $itemDate,
                        'type' => 'Discount',
                        'narration' => 'DISCOUNT - ' . $voteheadName,
                        'reference' => $invoice->invoice_number ?? 'N/A',
                        'votehead' => $voteheadName,
                        'term_name' => $invoice->term->name ?? '',
                        'term_name' => '',
                        'term_year' => $year,
                        'grade' => $student->currentClass->name ?? '',
                        'debit' => 0,
                        'credit' => $discountAmount,
                    ]);
                }
            }
        }
        
        // Add payments
        foreach ($payments as $payment) {
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
                        'grade' => $student->currentClass->name ?? '',
                        'debit' => 0,
                        'credit' => $payment->amount,
                    ]);
                } else {
                    foreach ($payment->allocations as $allocation) {
                        $item = $allocation->invoiceItem;
                        $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
                        $detailedTransactions->push([
                            'date' => $payment->payment_date,
                            'type' => 'Payment',
                            'narration' => 'RECEIPT - ' . ($payment->receipt_number ?? 'N/A') . ' - ' . ($payment->paymentMethod->name ?? 'CASH'),
                            'reference' => $payment->receipt_number ?? 'N/A',
                            'votehead' => $voteheadName,
                            'term_year' => $year,
                            'grade' => $student->currentClass->name ?? '',
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
            $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Credit Note',
                'narration' => 'CREDIT NOTE - ' . $voteheadName . ' - ' . ($note->credit_note_number ?? 'CN-' . $note->id),
                'reference' => $note->credit_note_number ?? 'CN-' . $note->id,
                'votehead' => $voteheadName,
                'term_year' => $year,
                'grade' => $student->currentClass->name ?? '',
                'debit' => 0,
                'credit' => $note->amount,
            ]);
        }
        
        // Add debit notes
        foreach ($debitNotes as $note) {
            $item = $note->invoiceItem;
            $voteheadName = $item->votehead->name ?? 'Unknown Votehead';
            $detailedTransactions->push([
                'date' => $note->created_at,
                'type' => 'Debit Note',
                'narration' => 'DEBIT NOTE - ' . $voteheadName . ' - ' . ($note->debit_note_number ?? 'DN-' . $note->id),
                'reference' => $note->debit_note_number ?? 'DN-' . $note->id,
                'votehead' => $voteheadName,
                'term_year' => $year,
                'grade' => $student->currentClass->name ?? '',
                'debit' => $note->amount,
                'credit' => 0,
            ]);
        }
        
        // Sort by date
        $detailedTransactions = $detailedTransactions->sortBy('date')->values();
        
        // Calculate totals
        $totalDebit = $detailedTransactions->sum('debit');
        $totalCredit = $detailedTransactions->sum('credit');
        
        // Calculate balance properly
        // For legacy years (pre-2026), use ending_balance from last term
        // For 2026+, use invoice balance + balance brought forward
        if ($year < 2026) {
            $lastLegacyTerm = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
                ->where('academic_year', $year)
                ->orderByDesc('term_number')
                ->first();
            $finalBalance = $lastLegacyTerm->ending_balance ?? 0;
        } else {
            $totalCharges = $invoices->sum(function($inv) {
                return $inv->items->sum('amount') ?? 0;
            });
            $totalDiscounts = $invoices->sum('discount_amount') + $invoices->sum(function($inv) {
                return $inv->items->sum('discount_amount') ?? 0;
            });
            // Use amounts allocated to these invoices, not full payment.amount (avoids double-count and wrong scope)
            $totalPayments = $invoices->sum('paid_amount');
            $totalCreditNotes = $creditNotes->sum('amount');
            $totalDebitNotes = $debitNotes->sum('amount');
            $invoiceBalance = $totalCharges - $totalDiscounts - $totalPayments + $totalDebitNotes - $totalCreditNotes;
            $balanceBroughtForward = \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
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
            'finalBalance'
        ));
    }
    
    public function export(Request $request, Student $student)
    {
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        $format = $request->get('format', 'pdf'); // pdf or csv
        
        // Get the same data as show method (exclude reversed invoices)
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where('year', $year)
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


<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\FeeConcession;
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
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        
        // Get all invoices for the student
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where('year', $year)
            ->with(['items.votehead', 'term', 'academicYear', 'items.creditNotes', 'items.debitNotes']);
            
        if ($term) {
            $invoicesQuery->whereHas('term', function($q) use ($term) {
                $q->where('name', 'like', "%Term {$term}%")
                  ->orWhere('id', $term);
            });
        }
        
        $invoices = $invoicesQuery->orderBy('created_at')->get();
        
        // Get all payments
        $paymentsQuery = Payment::where('student_id', $student->id)
            ->whereYear('payment_date', $year);
            
        if ($term) {
            // Filter payments by term if needed
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
        })->whereYear('created_at', $year)->with(['invoiceItem.invoice', 'issuedBy']);
        
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
        })->whereYear('created_at', $year)->with(['invoiceItem.invoice', 'issuedBy']);
        
        if ($term) {
            $debitNotesQuery->whereHas('invoiceItem.invoice', function($q) use ($term) {
                $q->whereHas('term', function($q2) use ($term) {
                    $q2->where('name', 'like', "%Term {$term}%")
                       ->orWhere('id', $term);
                });
            });
        }
        
        $debitNotes = $debitNotesQuery->orderBy('created_at')->get();
        
        // Calculate totals - invoices already have discounts applied in their total
        $totalCharges = $invoices->sum('total'); // This already includes discounts
        $totalDiscounts = $discounts->sum('value') + $invoices->sum('discount_amount') + $invoices->sum(function($inv) {
            return $inv->items->sum('discount_amount');
        });
        $totalPayments = $payments->sum('amount');
        $totalCreditNotes = $creditNotes->sum('amount');
        $totalDebitNotes = $debitNotes->sum('amount');
        
        // Calculate balance from transaction history (more accurate)
        // Start with 0, add debits, subtract credits
        $calculatedBalance = 0;
        foreach ($invoices as $invoice) {
            $calculatedBalance += $invoice->total; // Debit
        }
        foreach ($payments as $payment) {
            $calculatedBalance -= $payment->amount; // Credit
        }
        foreach ($discounts as $discount) {
            $calculatedBalance -= $discount->value; // Credit
        }
        foreach ($creditNotes as $note) {
            $calculatedBalance -= $note->amount; // Credit
        }
        foreach ($debitNotes as $note) {
            $calculatedBalance += $note->amount; // Debit
        }
        
        // Use calculated balance (matches transaction history)
        $balance = $calculatedBalance;
        
        // Get all terms and years for filter
        $terms = \App\Models\Term::orderBy('name')->get();
        $years = Invoice::where('student_id', $student->id)
            ->distinct()
            ->pluck('year')
            ->sort()
            ->reverse();
        
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
            'year',
            'term',
            'terms',
            'years'
        ));
    }

    public function export(Request $request, Student $student)
    {
        $year = $request->get('year', now()->year);
        $term = $request->get('term');
        $format = $request->get('format', 'pdf'); // pdf or csv
        
        // Get the same data as show method
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->where('year', $year)
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
            
            // Summary
            $totalCharges = $invoices->sum('total');
            $totalPayments = $payments->sum('amount');
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
        
        // For PDF, we'll use the same view but with print styles
        return view('finance.student_statements.print', compact(
            'student',
            'invoices',
            'payments',
            'discounts',
            'year',
            'term',
            'terms'
        ));
    }
}


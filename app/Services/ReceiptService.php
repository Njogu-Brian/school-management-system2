<?php

namespace App\Services;

use App\Models\{Payment, BankAccount};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

/**
 * Receipt Service
 * Handles PDF receipt generation with configurable templates
 */
class ReceiptService
{
    /**
     * Generate PDF receipt for payment
     */
    public function generateReceipt(Payment $payment, array $options = []): string
    {
        $payment->load([
            'student.classroom', 
            'invoice', 
            'paymentMethod', 
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);
        
        // Get school settings
        $schoolSettings = $this->getSchoolSettings();
        
        $student = $payment->student;
        
        // Get ALL unpaid invoice items for the student (not just allocated ones)
        $allUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->where('status', 'active')
        ->with(['invoice', 'votehead', 'allocations'])
        ->get()
        ->filter(function($item) {
            return $item->getBalance() > 0; // Only unpaid items
        });
        
        // Get payment allocations for this specific payment
        $paymentAllocations = $payment->allocations;
        
        // Build comprehensive receipt items showing:
        // 1. Items that received payment (with allocation details)
        // 2. All other unpaid items (with their balances)
        $receiptItems = collect();
        
        // First, add items that received payment from this payment
        foreach ($paymentAllocations as $allocation) {
            $item = $allocation->invoiceItem;
            $itemAmount = $item->amount ?? 0;
            $discountAmount = $item->discount_amount ?? 0;
            $allocatedAmount = $allocation->amount;
            $balanceBefore = $item->getBalance() + $allocatedAmount; // Balance before this payment
            $balanceAfter = $item->getBalance(); // Balance after this payment
            
            $receiptItems->push([
                'type' => 'paid',
                'allocation' => $allocation,
                'invoice' => $item->invoice ?? null,
                'votehead' => $item->votehead ?? null,
                'item_amount' => $itemAmount,
                'discount_amount' => $discountAmount,
                'allocated_amount' => $allocatedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);
        }
        
        // Then, add all other unpaid items that didn't receive payment
        $paidItemIds = $paymentAllocations->pluck('invoice_item_id')->toArray();
        foreach ($allUnpaidItems as $item) {
            // Skip if already included in paid items
            if (in_array($item->id, $paidItemIds)) {
                continue;
            }
            
            $itemAmount = $item->amount ?? 0;
            $discountAmount = $item->discount_amount ?? 0;
            $balance = $item->getBalance();
            
            $receiptItems->push([
                'type' => 'unpaid',
                'allocation' => null,
                'invoice' => $item->invoice ?? null,
                'votehead' => $item->votehead ?? null,
                'item_amount' => $itemAmount,
                'discount_amount' => $discountAmount,
                'allocated_amount' => 0,
                'balance_before' => $balance,
                'balance_after' => $balance,
            ]);
        }
        
        // Calculate totals
        $totalBalanceBefore = $receiptItems->sum('balance_before');
        $totalBalanceAfter = $receiptItems->sum('balance_after');
        
        // Calculate TOTAL outstanding balance and total invoices across ALL voteheads for the student
        // Optimize: Use direct queries instead of recalculating each invoice
        $invoices = \App\Models\Invoice::where('student_id', $student->id)
            ->with('items') // Eager load items to avoid N+1
            ->get();
        
        $totalOutstandingBalance = 0;
        $totalInvoices = 0;
        
        // Calculate totals without recalculating each invoice (use existing balance if accurate)
        foreach ($invoices as $invoice) {
            // Use existing balance if invoice was recently updated, otherwise recalculate
            if ($invoice->updated_at && $invoice->updated_at->gt(now()->subMinutes(5))) {
                // Recently updated, use existing balance
                $totalOutstandingBalance += max(0, $invoice->balance ?? 0);
            } else {
                // Recalculate only if needed
                $invoice->recalculate();
                $totalOutstandingBalance += max(0, $invoice->balance ?? 0);
            }
            $totalInvoices += $invoice->total ?? 0;
        }
        
        // Use receiptItems instead of allocations for template
        $allocations = $receiptItems;
        
        // Prepare data for PDF
        $data = [
            'payment' => $payment,
            'school' => $schoolSettings,
            'receipt_number' => $payment->receipt_number,
            'date' => $payment->payment_date->format('d/m/Y'),
            'student' => $student,
            'allocations' => $allocations,
            'total_amount' => $payment->amount,
            'total_balance_before' => $totalBalanceBefore,
            'total_balance_after' => $totalBalanceAfter,
            'total_outstanding_balance' => $totalOutstandingBalance, // Total across all voteheads
            'total_invoices' => $totalInvoices, // Total of all invoices
            'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
            'transaction_code' => $payment->transaction_code,
            'narration' => $payment->narration,
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('finance.receipts.pdf.template', $data);
        
        // Set paper size
        $paperSize = $options['paper_size'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'portrait';
        $pdf->setPaper($paperSize, $orientation);
        
        // Save to storage if requested
        if ($options['save'] ?? false) {
            $filename = 'receipts/receipt_' . $payment->receipt_number . '_' . time() . '.pdf';
            Storage::disk('public')->put($filename, $pdf->output());
            return $filename;
        }
        
        // Return PDF content
        return $pdf->output();
    }
    
    /**
     * Download receipt PDF
     */
    public function downloadReceipt(Payment $payment): \Illuminate\Http\Response
    {
        $pdf = $this->generateReceipt($payment);
        $filename = 'Receipt_' . $payment->receipt_number . '.pdf';
        
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    
    /**
     * Get school settings for receipt header/footer
     */
    private function getSchoolSettings(): array
    {
        // Try Setting model first
        if (class_exists(\App\Models\Setting::class)) {
            $settings = \App\Models\Setting::whereIn('key', [
                'school_name',
                'school_logo',
                'school_address',
                'school_phone',
                'school_email',
                'school_registration_number',
            ])->pluck('value', 'key')->toArray();
        } else {
            // Fallback to direct table query
            $settings = DB::table('settings')->whereIn('key', [
                'school_name',
                'school_logo',
                'school_address',
                'school_phone',
                'school_email',
                'school_registration_number',
            ])->pluck('value', 'key')->toArray();
        }
        
        return [
            'name' => $settings['school_name'] ?? 'School Name',
            'logo' => $settings['school_logo'] ?? null,
            'address' => $settings['school_address'] ?? '',
            'phone' => $settings['school_phone'] ?? '',
            'email' => $settings['school_email'] ?? '',
            'registration_number' => $settings['school_registration_number'] ?? '',
        ];
    }
    
    /**
     * Get payment allocations summary
     */
    public function getAllocationsSummary(Payment $payment): array
    {
        $allocations = $payment->allocations()->with('invoiceItem.votehead', 'invoiceItem.invoice')->get();
        
        return [
            'total_allocated' => $allocations->sum('amount'),
            'items' => $allocations->map(function ($allocation) {
                return [
                    'invoice_number' => $allocation->invoiceItem->invoice->invoice_number ?? 'N/A',
                    'votehead' => $allocation->invoiceItem->votehead->name ?? 'N/A',
                    'amount' => $allocation->amount,
                ];
            }),
        ];
    }
}


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
     * Build receipt data array for a single payment
     */
    public function buildReceiptData(Payment $payment): array
    {
        $payment->load([
            'student.classroom',
            'student.family.updateLink',
            'invoice',
            'paymentMethod',
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);

        // Get school settings and document header/footer
        $schoolSettings = $this->getSchoolSettings();
        $branding = $this->getBranding();
        $receiptHeader = \App\Models\Setting::get('receipt_header', '');
        $receiptFooter = \App\Models\Setting::get('receipt_footer', '');

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

        // Calculate TOTAL outstanding balance including balance brought forward
        $totalOutstandingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);

        // Calculate total invoices for receipt display
        $invoices = \App\Models\Invoice::where('student_id', $student->id)
            ->with('items') // Eager load items to avoid N+1
            ->get();

        $totalInvoices = $invoices->sum('total');

        // Use receiptItems instead of allocations for template
        $allocations = $receiptItems;
        // Show this payment's actual receipt number (systematic: base for first, base-01, base-02 for siblings)
        $displayReceiptNumber = $payment->receipt_number;

        return [
            'payment' => $payment,
            'school' => $schoolSettings,
            'branding' => $branding,
            'receipt_number' => $displayReceiptNumber,
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
            'receipt_header' => $receiptHeader,
            'receipt_footer' => $receiptFooter,
        ];
    }

    /**
     * Generate PDF receipt for payment
     */
    public function generateReceipt(Payment $payment, array $options = []): string
    {
        $data = $this->buildReceiptData($payment);

        $sharedReceiptNumber = $payment->shared_receipt_number;
        if ($sharedReceiptNumber) {
            $sharedPayments = Payment::where('shared_receipt_number', $sharedReceiptNumber)
                ->orderBy('id')
                ->get();

            $receipts = $sharedPayments->map(function ($sharedPayment) {
                return $this->buildReceiptData($sharedPayment);
            })->values()->all();

            $pdf = Pdf::loadView('finance.receipts.bulk-print-pdf', [
                'receipts' => $receipts,
                'school' => $data['school'],
                'branding' => $data['branding'],
                'receiptHeader' => $data['receipt_header'],
                'receiptFooter' => $data['receipt_footer'],
            ]);
        } else {
            $pdf = Pdf::loadView('finance.receipts.pdf.template', $data);
        }
        
        // Set paper size
        $paperSize = $options['paper_size'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'portrait';
        $pdf->setPaper($paperSize, $orientation);
        
        // Save to storage if requested
        if ($options['save'] ?? false) {
            $filename = 'receipts/receipt_' . ($data['receipt_number'] ?? $payment->receipt_number) . '_' . time() . '.pdf';
            storage_public()->put($filename, $pdf->output());
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
     * Get school settings for receipt header/footer (public for use in views e.g. payment plan public page)
     */
    public function getSchoolSettings(): array
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
        
        $logoPath = null;
        if (!empty($settings['school_logo']) && \Illuminate\Support\Facades\storage_public()->exists($settings['school_logo'])) {
            $logoPath = storage_path('app/public/' . $settings['school_logo']);
        } elseif (!empty($settings['school_logo']) && file_exists(public_path('images/' . $settings['school_logo']))) {
            $logoPath = public_path('images/' . $settings['school_logo']);
        }

        return [
            'name' => $settings['school_name'] ?? 'School Name',
            'logo' => $settings['school_logo'] ?? null,
            'logo_path' => $logoPath,
            'address' => $settings['school_address'] ?? '',
            'phone' => $settings['school_phone'] ?? '',
            'email' => $settings['school_email'] ?? '',
            'registration_number' => $settings['school_registration_number'] ?? '',
        ];
    }
    
    /**
     * Get branding information with logo base64
     */
    private function getBranding(): array
    {
        $kv = DB::table('settings')->pluck('value','key')->map(fn($v) => trim((string)$v));

        $name    = $kv['school_name']    ?? config('app.name', 'Your School');
        $email   = $kv['school_email']   ?? 'info@example.com';
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
            if (!is_file($path)) continue;

            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');

            // If it's a PNG but neither GD nor Imagick is available, skip embedding to avoid DomPDF fatal
            if ($mime === 'image/png' && !extension_loaded('gd') && !extension_loaded('imagick')) {
                $logoBase64 = null;
                break;
            }

            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
            break;
        }

        return compact('name','email','phone','website','address','logoBase64');
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


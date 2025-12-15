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
        $payment->load(['student', 'invoice', 'paymentMethod', 'bankAccount', 'allocations.invoiceItem.votehead']);
        
        // Get school settings
        $schoolSettings = $this->getSchoolSettings();
        
        // Prepare data for PDF
        $data = [
            'payment' => $payment,
            'school' => $schoolSettings,
            'receipt_number' => $payment->receipt_number,
            'date' => $payment->payment_date->format('d/m/Y'),
            'student' => $payment->student,
            'allocations' => $payment->allocations,
            'total_amount' => $payment->amount,
            'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
            'reference' => $payment->reference,
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
            $filename = 'receipts/' . $payment->receipt_number . '.pdf';
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


<?php

namespace App\Services;

use App\Models\{Payment, BankAccount, FeePaymentPlan};
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
            'invoice.term.academicYear',
            'paymentMethod',
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice.term.academicYear',
        ]);

        // Get school settings and document header/footer
        $schoolSettings = $this->getSchoolSettings();
        $branding = $this->getBranding();
        $receiptHeader = \App\Models\Setting::get('receipt_header', '');
        $receiptFooter = \App\Models\Setting::get('receipt_footer', '');

        $student = $payment->student;

        // Get payment allocations for this specific payment
        $paymentAllocations = $payment->allocations;

        // Keep receipt context scoped to the payment's term(s) to avoid mixing charges
        // from different terms in a single receipt.
        $contextTermIds = $paymentAllocations
            ->map(function ($allocation) {
                return optional(optional($allocation->invoiceItem)->invoice)->term_id;
            })
            ->filter()
            ->unique()
            ->values();

        if ($contextTermIds->isEmpty() && optional($payment->invoice)->term_id) {
            $contextTermIds = collect([$payment->invoice->term_id]);
        }

        // Get unpaid invoice items for the student, scoped to receipt context term(s) where possible.
        $allUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student, $contextTermIds) {
            $q->where('student_id', $student->id)
                ->where('status', '!=', 'reversed');

            if ($contextTermIds->isNotEmpty()) {
                $q->whereIn('term_id', $contextTermIds->all());
            }
        })
            ->where('status', 'active')
            ->with(['invoice.term', 'votehead', 'allocations'])
            ->get()
            ->filter(function ($item) {
                return $item->getBalance() > 0; // Only unpaid items
            });

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

        // Calculate total invoices for receipt display in the same term context
        $invoices = \App\Models\Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'reversed')
            ->when($contextTermIds->isNotEmpty(), function ($q) use ($contextTermIds) {
                $q->whereIn('term_id', $contextTermIds->all());
            }, function ($q) use ($payment) {
                if ($payment->invoice_id) {
                    $q->where('id', $payment->invoice_id);
                }
            })
            // Eager load items and term+academicYear on the query. Do not use loadMissing('term.*') here:
            // Invoice has a legacy `term` column that shadows the term() BelongsTo; Eloquent's
            // loadMissing plucks by relation name and receives ints, breaking nested loads.
            ->with(['items', 'term.academicYear'])
            ->get();

        $totalInvoices = $invoices->sum('total');

        // NOTE:
        // `Invoice` has a legacy `term` column that conflicts with the `term()` relation.
        // Using `pluck('term')` can return the column value (often an int) instead of the
        // loaded `Term` model, which then breaks `$term->academicYear`.
        $termLabels = $invoices
            ->map(function ($invoice) {
                /** @var mixed $term */
                $term = $invoice->relationLoaded('term') ? $invoice->getRelation('term') : null;

                if (!is_object($term) || !method_exists($term, 'academicYear')) {
                    return null;
                }

                $year = $term->academicYear?->year;
                $name = $term->name ?? '';

                return trim($name . ($year ? ' (' . $year . ')' : ''));
            })
            ->filter()
            ->unique()
            ->values();
        $receiptTermLabel = $termLabels->isEmpty() ? null : $termLabels->implode(', ');
        $invoiceNumbersSummary = $invoices->pluck('invoice_number')->filter()->unique()->sort()->implode(', ');

        // Use receiptItems instead of allocations for template
        $allocations = $receiptItems;
        // Show this payment's actual receipt number (systematic: base for first, base-01, base-02 for siblings)
        $displayReceiptNumber = $payment->receipt_number;
        $receiptDate = $payment->receipt_date ?? $payment->payment_date;

        return [
            'payment' => $payment,
            'school' => $schoolSettings,
            'branding' => $branding,
            'receipt_number' => $displayReceiptNumber,
            'date' => $receiptDate ? $receiptDate->format('d/m/Y') : now()->format('d/m/Y'),
            'student' => $student,
            'allocations' => $allocations,
            'total_amount' => $payment->amount,
            'total_balance_before' => $totalBalanceBefore,
            'total_balance_after' => $totalBalanceAfter,
            'total_outstanding_balance' => $totalOutstandingBalance, // Global (e.g. Pay Now); receipt Balance row uses total_balance_after
            'total_invoices' => $totalInvoices, // Invoices in this receipt's term context only
            'receipt_term_label' => $receiptTermLabel,
            'invoice_numbers_summary' => $invoiceNumbersSummary ?: null,
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
        if (!empty($settings['school_logo']) && storage_public()->exists($settings['school_logo'])) {
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
     * Branding for PDFs (receipts, payment plan agreements, etc.)
     */
    public function getDocumentBranding(): array
    {
        return $this->getBranding();
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
     * Data for payment plan agreement PDF / print view.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $preparedBy
     */
    public function buildPaymentPlanAgreementData(FeePaymentPlan $plan, $preparedBy = null): array
    {
        $plan->load([
            'student.classroom',
            'student.stream',
            'student.parent',
            'invoice.term.academicYear',
            'installments' => fn ($q) => $q->orderBy('installment_number'),
            'creator',
        ]);

        $student = $plan->student;
        $parent = $student?->parent;
        $parentDisplay = null;
        if ($parent) {
            $parentDisplay = $parent->primary_contact_person
                ?: $parent->father_name
                ?: $parent->mother_name
                ?: $parent->guardian_name;
        }

        $schoolSettings = $this->getSchoolSettings();
        $branding = $this->getDocumentBranding();

        $intro = '';
        if (class_exists(\App\Models\Setting::class)) {
            $intro = (string) \App\Models\Setting::get('payment_plan_agreement_intro', '');
        }
        if ($intro === '') {
            $intro = "The parent/guardian named below agrees to pay the school's fees according to the schedule in this document. "
                . 'Failure to meet agreed dates may result in the student\'s fee status being marked as pending and may affect attendance and transport per school policy.';
        }

        $preparedByName = $preparedBy ? (string) ($preparedBy->name ?? '') : '';

        return [
            'plan' => $plan,
            'student' => $student,
            'parent' => $parent,
            'parent_display_name' => $parentDisplay,
            'schoolSettings' => $schoolSettings,
            'branding' => $branding,
            'agreement_intro' => $intro,
            'prepared_by_name' => $preparedByName,
            'prepared_at' => now(),
        ];
    }

    /**
     * Generate payment plan agreement PDF binary.
     */
    public function generatePaymentPlanAgreementPdf(FeePaymentPlan $plan, $preparedBy = null): string
    {
        $data = $this->buildPaymentPlanAgreementData($plan, $preparedBy);
        $pdf = Pdf::loadView('finance.fee_payment_plans.pdf.agreement', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
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


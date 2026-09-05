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
        if (! $student && $payment->student_id) {
            $student = \App\Models\Student::withoutGlobalScopes()
                ->with(['classroom', 'family.updateLink'])
                ->find($payment->student_id);
        }
        if (! $student) {
            throw new \RuntimeException('Student record not found for payment #'.$payment->id);
        }

        $ledger = app(StudentFeeLedgerService::class)->snapshot((int) $student->id);
        $asAt = $this->balanceAsAtPayment($payment);

        $paymentAllocations = $payment->allocations;
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

        $invoices = \App\Models\Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'reversed')
            ->when($contextTermIds->isNotEmpty(), function ($q) use ($contextTermIds) {
                $q->whereIn('term_id', $contextTermIds->all());
            }, function ($q) use ($payment, $ledger) {
                if ($payment->invoice_id) {
                    $q->where('id', $payment->invoice_id);
                } elseif (!empty($ledger['invoices'])) {
                    $openIds = collect($ledger['invoices'])
                        ->filter(fn ($row) => ($row['balance'] ?? 0) > 0.009 || ($row['paid_amount'] ?? 0) > 0.009)
                        ->keys();
                    if ($openIds->isNotEmpty()) {
                        $q->whereIn('id', $openIds->all());
                    }
                }
            })
            ->with(['items.votehead', 'term.academicYear'])
            ->get();

        $receiptItems = collect();
        foreach ($invoices as $invoice) {
            foreach ($invoice->items->where('status', 'active') as $item) {
                $receiptItems->push([
                    'type' => 'charge',
                    'allocation' => null,
                    'invoice' => $invoice,
                    'votehead' => $item->votehead,
                    'item_amount' => (float) ($item->amount ?? 0),
                    'discount_amount' => (float) ($item->discount_amount ?? 0),
                    'allocated_amount' => 0,
                    'balance_before' => 0,
                    'balance_after' => 0,
                ]);
            }
        }

        $totalInvoices = (float) $invoices->sum(function ($invoice) use ($ledger) {
            return (float) ($ledger['invoices'][$invoice->id]['total'] ?? $invoice->total);
        });

        $termCoverage = \App\Services\Finance\PaymentTermCoverage::forPayment($payment);
        $termLabels = $invoices
            ->map(function ($invoice) {
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
        $receiptTermLabel = $termCoverage['summary_label'] !== ''
            ? $termCoverage['summary_label']
            : ($termLabels->isEmpty() ? null : $termLabels->implode(', '));
        $invoiceNumbersSummary = $invoices->pluck('invoice_number')->filter()->unique()->sort()->implode(', ');

        $displayReceiptNumber = $payment->receipt_number;
        $receiptDate = $payment->receipt_date ?? $payment->payment_date;
        $studentBalance = (float) $asAt['balance_after'];

        return [
            'payment' => $payment,
            'school' => $schoolSettings,
            'branding' => $branding,
            'receipt_number' => $displayReceiptNumber,
            'date' => $receiptDate ? $receiptDate->format('d/m/Y') : now()->format('d/m/Y'),
            'student' => $student,
            'allocations' => $receiptItems,
            'total_amount' => $payment->amount,
            'total_balance_before' => (float) $asAt['balance_before'],
            'total_balance_after' => $studentBalance,
            'total_outstanding_balance' => $studentBalance,
            'balance_as_at_payment' => $studentBalance,
            'total_invoices' => $totalInvoices,
            'receipt_term_label' => $receiptTermLabel,
            'term_coverage' => $termCoverage,
            'invoice_numbers_summary' => $invoiceNumbersSummary ?: null,
            'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
            'transaction_code' => $payment->transaction_code,
            'narration' => $payment->narration,
            'receipt_header' => $receiptHeader,
            'receipt_footer' => $receiptFooter,
        ];
    }

    /**
     * Balance frozen on this receipt. Later payments must not change it.
     */
    private function balanceAsAtPayment(Payment $payment): array
    {
        $computed = app(StudentFeeStatementService::class)->balanceAfterPayment($payment);
        if ($payment->balance_after === null && \Illuminate\Support\Facades\Schema::hasColumn('payments', 'balance_after')) {
            Payment::withoutEvents(function () use ($payment, $computed) {
                Payment::where('id', $payment->id)->whereNull('balance_after')->update([
                    'balance_before' => $computed['balance_before'],
                    'balance_after' => $computed['balance_after'],
                    'updated_at' => now(),
                ]);
            });
            $payment->balance_before = $computed['balance_before'];
            $payment->balance_after = $computed['balance_after'];
        }

        return $computed;
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


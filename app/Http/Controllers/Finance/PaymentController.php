<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationLog;
use App\Services\PaymentAllocationService;
use App\Services\ReceiptService;
use App\Services\SMSService;
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\GenericMail;

class PaymentController extends Controller
{
    protected PaymentAllocationService $allocationService;
    protected ReceiptService $receiptService;
    protected SMSService $smsService;
    protected CommunicationService $commService;

    public function __construct(
        PaymentAllocationService $allocationService,
        ReceiptService $receiptService,
        SMSService $smsService,
        CommunicationService $commService
    ) {
        $this->allocationService = $allocationService;
        $this->receiptService = $receiptService;
        $this->smsService = $smsService;
        $this->commService = $commService;
    }

    public function index()
    {
        $payments = Payment::with(['student', 'paymentMethod', 'invoice'])
            ->latest('payment_date')
            ->paginate(20);
        
        return view('finance.payments.index', compact('payments'));
    }

    public function create()
    {
        $bankAccounts = \App\Models\BankAccount::active()->get();
        $paymentMethods = \App\Models\PaymentMethod::active()->get();
        return view('finance.payments.create', compact('bankAccounts', 'paymentMethods'));
    }

    public function getStudentBalanceAndSiblings(Student $student)
    {
        $studentId = $student->id;
        $invoices = Invoice::where('student_id', $studentId)->get();
        
        $totalBalance = $invoices->sum('balance');
        $unpaidInvoices = $invoices->where('balance', '>', 0)->count();
        $partialInvoices = $invoices->where('balance', '>', 0)->where('balance', '<', $invoices->sum('total'))->count();
        
        // Get siblings (excluding current student)
        $siblings = $student->family 
            ? $student->family->students()->where('id', '!=', $studentId)->get()->map(function($sibling) {
                $siblingInvoices = Invoice::where('student_id', $sibling->id)->get();
                return [
                    'id' => $sibling->id,
                    'name' => $sibling->first_name . ' ' . $sibling->last_name,
                    'admission_number' => $sibling->admission_number,
                    'balance' => $siblingInvoices->sum('balance'),
                ];
            })
            : collect();
        
        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'admission_number' => $student->admission_number,
            ],
            'balance' => [
                'total_balance' => $totalBalance,
                'unpaid_invoices' => $unpaidInvoices,
                'partial_invoices' => $partialInvoices,
            ],
            'siblings' => $siblings,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payer_name' => 'nullable|string|max:255',
            'payer_type' => 'nullable|in:parent,sponsor,student,other',
            'narration' => 'nullable|string',
            'transaction_code' => 'required|string|unique:payments,transaction_code', // Transaction code must be unique
            'auto_allocate' => 'nullable|boolean',
            'allocations' => 'nullable|array', // Manual allocations
            'allocations.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'shared_payment' => 'nullable|boolean', // For payment sharing among siblings
            'shared_students' => 'nullable|array', // Array of student IDs for shared payment
            'shared_amounts' => 'nullable|array', // Array of amounts for each student
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Check for overpayment warning
        $invoice = isset($validated['invoice_id']) && $validated['invoice_id'] ? \App\Models\Invoice::find($validated['invoice_id']) : null;
        
        // Calculate student balance from invoices
        $studentInvoices = Invoice::where('student_id', $student->id)->get();
        $balance = $invoice ? $invoice->balance : $studentInvoices->sum('balance');
        $isOverpayment = $validated['amount'] > $balance;
        
        if ($isOverpayment && !($request->has('confirm_overpayment') && $request->confirm_overpayment)) {
            return back()
                ->withInput()
                ->with('warning', "Warning: Payment amount (Ksh " . number_format($validated['amount'], 2) . ") exceeds balance (Ksh " . number_format($balance, 2) . "). Overpayment of Ksh " . number_format($validated['amount'] - $balance, 2) . " will be carried forward.")
                ->with('show_overpayment_confirm', true);
        }

        $createdPayment = null;

        DB::transaction(function () use ($validated, $student, $isOverpayment, &$createdPayment) {
            // Handle payment sharing among siblings
            if ($validated['shared_payment'] ?? false && !empty($validated['shared_students'])) {
                $sharedStudents = $validated['shared_students'];
                $sharedAmounts = $validated['shared_amounts'] ?? [];
                $totalShared = array_sum($sharedAmounts);
                
                // Validate total shared equals payment amount
                if (abs($totalShared - $validated['amount']) > 0.01) {
                    throw new \Exception('Total shared amounts must equal payment amount.');
                }
                
                // Create payments for each sibling
                foreach ($sharedStudents as $index => $siblingId) {
                    $sibling = Student::findOrFail($siblingId);
                    $siblingAmount = $sharedAmounts[$index] ?? 0;
                    
                    if ($siblingAmount > 0) {
                        // Generate unique transaction code for each sibling payment
                        $transactionCode = $validated['transaction_code'] . '-' . ($index + 1);
                        
                        $payment = Payment::create([
                            'student_id' => $siblingId,
                            'family_id' => $sibling->family_id,
                            'invoice_id' => null, // Will be auto-allocated
                            'amount' => $siblingAmount,
                            'payment_method_id' => $validated['payment_method_id'],
                            'payer_name' => $validated['payer_name'],
                            'payer_type' => $validated['payer_type'],
                            'narration' => $validated['narration'],
                            'transaction_code' => $transactionCode,
                            'payment_date' => $validated['payment_date'],
                            // receipt_date is set automatically in Payment model
                        ]);
                        
                        // Auto-allocate for sibling
                        try {
                            if (method_exists($this->allocationService, 'autoAllocate')) {
                                $this->allocationService->autoAllocate($payment);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Sibling auto-allocation failed: ' . $e->getMessage());
                            // Continue - payment is still created
                        }
                        
                        // Store first payment for notifications
                        if ($index === 0) {
                            $createdPayment = $payment;
                        }
                    }
                }
            } else {
                // Create single payment
                $payment = Payment::create([
                    'student_id' => $validated['student_id'],
                    'family_id' => $student->family_id,
                    'invoice_id' => isset($validated['invoice_id']) ? $validated['invoice_id'] : null,
                    'amount' => $validated['amount'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'payer_name' => $validated['payer_name'],
                    'payer_type' => $validated['payer_type'],
                    'narration' => $validated['narration'],
                    'transaction_code' => $validated['transaction_code'],
                    'payment_date' => $validated['payment_date'],
                    // receipt_date is set automatically in Payment model
                ]);

                // Allocate payment
                if (isset($validated['auto_allocate']) && $validated['auto_allocate']) {
                    try {
                        $this->allocationService->autoAllocate($payment);
                    } catch (\Exception $e) {
                        Log::warning('Auto-allocation failed: ' . $e->getMessage());
                        // Continue without allocation - payment is still created
                    }
                } elseif (!empty($validated['allocations'])) {
                    try {
                        $this->allocationService->allocatePayment($payment, $validated['allocations']);
                    } catch (\Exception $e) {
                        Log::warning('Manual allocation failed: ' . $e->getMessage());
                        // Continue without allocation - payment is still created
                    }
                }
                
                // Handle overpayment
                try {
                    if (method_exists($payment, 'hasOverpayment') && $payment->hasOverpayment()) {
                        if (method_exists($this->allocationService, 'handleOverpayment')) {
                            $this->allocationService->handleOverpayment($payment);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Overpayment handling failed: ' . $e->getMessage());
                    // Continue - overpayment will be handled later
                }
                
                // Log audit
                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::log(
                        'created',
                        $payment,
                        null,
                        [
                            'amount' => $payment->amount,
                            'student_id' => $payment->student_id,
                            'payment_method_id' => $payment->payment_method_id,
                        ],
                        ['payment_recorded']
                    );
                }
                
                $createdPayment = $payment;
            }
        });

        // Send notifications
        try {
            $this->sendPaymentNotifications($createdPayment);
        } catch (\Exception $e) {
            Log::error('Payment notification failed', [
                'payment_id' => $createdPayment->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // Don't fail payment creation if notification fails
        }
        
        // Check if payment was created
        if (!$createdPayment) {
            Log::error('Payment creation failed - no payment created', [
                'validated' => $validated,
                'student_id' => $validated['student_id'] ?? null
            ]);
            return back()
                ->withInput()
                ->with('error', 'Payment creation failed. Please try again.');
        }
        
        // Return with payment ID for receipt popup
        return redirect()
            ->route('finance.payments.index')
            ->with('success', 'Payment recorded successfully.')
            ->with('payment_id', $createdPayment->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info('Payment validation failed', ['errors' => $e->errors()]);
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->except(['_token'])
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Payment creation failed: ' . $e->getMessage());
        }
    }
    
    protected function sendPaymentNotifications(Payment $payment)
    {
        $payment->load(['student.parent', 'paymentMethod']);
        $student = $payment->student;
        
        // Get parent contact info
        $parent = $student->parent;
        
        if (!$parent) {
            Log::info('No parent found for payment notification', ['payment_id' => $payment->id, 'student_id' => $student->id]);
            return;
        }
        
        // Get primary contact phone and email from ParentInfo model
        $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
        $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
        
        if (!$parentPhone && !$parentEmail) {
            Log::info('No parent contact info found for payment notification', ['payment_id' => $payment->id]);
            return;
        }
        
        // Get or create payment receipt template
        // Include public receipt link in SMS
        $smsTemplate = CommunicationTemplate::firstOrCreate(
            ['code' => 'payment_receipt_sms'],
            [
                'title' => 'Payment Receipt SMS',
                'type' => 'sms',
                'subject' => 'Payment Receipt',
                'content' => 'Dear {{parent_name}}, Payment of Ksh {{amount}} received for {{student_name}} ({{admission_number}}). Receipt #{{receipt_number}}. View: {{receipt_link}}',
            ]
        );
        
        // Update existing template to include receipt link if it doesn't have it
        if (strpos($smsTemplate->content, '{{receipt_link}}') === false) {
            $smsTemplate->content = 'Dear {{parent_name}}, Payment of Ksh {{amount}} received for {{student_name}} ({{admission_number}}). Receipt #{{receipt_number}}. View: {{receipt_link}}';
            $smsTemplate->save();
        }
        
        $emailTemplate = CommunicationTemplate::firstOrCreate(
            ['code' => 'payment_receipt_email'],
            [
                'title' => 'Payment Receipt Email',
                'type' => 'email',
                'subject' => 'Payment Receipt - {{receipt_number}}',
                'content' => '<p>Dear {{parent_name}},</p><p>Payment of <strong>Ksh {{amount}}</strong> has been received for <strong>{{student_name}}</strong> (Admission: {{admission_number}}).</p><p><strong>Receipt Number:</strong> {{receipt_number}}<br><strong>Transaction Code:</strong> {{transaction_code}}<br><strong>Payment Date:</strong> {{payment_date}}</p><p>Please find the receipt attached.</p><p><a href="{{receipt_link}}">View Receipt Online</a></p>',
            ]
        );
        
        // Prepare template variables
        // Use public receipt link (token-based, no ID in URL)
        // Ensure payment has public_token (generate if missing)
        if (!$payment->public_token) {
            $payment->public_token = \App\Models\Payment::generatePublicToken();
            $payment->save();
        }
        
        try {
            // Use public_token (10 chars) for receipt link in communications
            // This is only for external/parent communications, not internal portal
            // Use url() helper which respects APP_URL, then normalize to fix any port issues
            $receiptLink = url('/receipt/' . $payment->public_token);
            $receiptLink = $this->normalizeUrl($receiptLink);
        } catch (\Exception $e) {
            Log::error('Failed to generate receipt link', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Fallback: use a simple message without link
            $receiptLink = 'Contact school for receipt details';
        }
        
        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent';
        
        // Calculate outstanding balance for the student (after this payment)
        // Refresh payment to ensure allocations are loaded
        $payment->refresh();
        
        // Get all invoices for the student and refresh to get latest balances
        // (Allocation service should have already recalculated them)
        $studentInvoices = \App\Models\Invoice::where('student_id', $student->id)->get();
        
        // Recalculate invoices to ensure balances are up to date after payment allocation
        // This ensures accuracy even if allocation didn't trigger recalculation
        foreach ($studentInvoices as $invoice) {
            try {
                if (class_exists(\App\Services\InvoiceService::class) && method_exists(\App\Services\InvoiceService::class, 'recalc')) {
                    \App\Services\InvoiceService::recalc($invoice);
                } elseif (method_exists($invoice, 'recalculate')) {
                    $invoice->recalculate();
                }
            } catch (\Exception $e) {
                // Log but continue - balance will be from current invoice state
                Log::debug('Invoice recalculation in notification: ' . $e->getMessage());
            }
        }
        
        // Refresh invoices to get updated balances
        $studentInvoices->each->refresh();
        
        // Calculate total outstanding balance after payment
        $outstandingBalance = $studentInvoices->sum('balance');
        
        $variables = [
            'parent_name' => $parentName,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'amount' => number_format($payment->amount, 2),
            'receipt_number' => $payment->receipt_number,
            'transaction_code' => $payment->transaction_code,
            'payment_date' => $payment->payment_date->format('d M Y'),
            'receipt_link' => $receiptLink,
            'outstanding_amount' => number_format($outstandingBalance, 2),
        ];
        
        // Replace placeholders
        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };
        
        // Send SMS
        if ($parentPhone) {
            try {
                $smsMessage = $replacePlaceholders($smsTemplate->content, $variables);
                $this->commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, $smsTemplate->subject ?? $smsTemplate->title);
                Log::info('Payment SMS sent successfully', [
                    'payment_id' => $payment->id, 
                    'phone' => $parentPhone,
                    'receipt_link' => $receiptLink
                ]);
            } catch (\Exception $e) {
                Log::error('Payment SMS sending failed', [
                    'error' => $e->getMessage(), 
                    'payment_id' => $payment->id, 
                    'phone' => $parentPhone,
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw - allow email to still be sent
            }
        }
        
        // Send Email with PDF attachment
        if ($parentEmail) {
            try {
                $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                
                // Generate PDF receipt
                $pdfPath = $this->receiptService->generateReceipt($payment, ['save' => true]);
                
                // Use CommunicationService to send email (handles logging automatically)
                $this->commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent, $pdfPath);
                Log::info('Payment email sent successfully', ['payment_id' => $payment->id, 'email' => $parentEmail]);
            } catch (\Exception $e) {
                Log::error('Email sending failed', ['error' => $e->getMessage(), 'payment_id' => $payment->id, 'trace' => $e->getTraceAsString()]);
            }
        }
    }
    
    public function allocate(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'allocations' => 'required|array',
            'allocations.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->allocationService->allocatePayment($payment, $validated['allocations']);
            return redirect()
                ->route('finance.payments.show', $payment)
                ->with('success', 'Payment allocated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        $payment->load(['student', 'invoice', 'paymentMethod', 'allocations.invoiceItem.votehead']);
        return view('finance.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        // Payment editing might be restricted - implement as needed
        return back()->with('error', 'Payment editing is not allowed. Please reverse the payment and create a new one.');
    }

    public function update(Request $request, Payment $payment)
    {
        // Payment updates might be restricted
        return back()->with('error', 'Payment updates are not allowed.');
    }

    public function destroy(Payment $payment)
    {
        // Instead of delete, reverse the payment
        return redirect()
            ->route('finance.payments.reverse', $payment)
            ->with('info', 'Use the reverse payment feature instead.');
    }
    
    public function reverse(Payment $payment)
    {
        if ($payment->reversed) {
            return back()->with('error', 'This payment has already been reversed.');
        }
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            // Collect invoice IDs from allocations before deleting them
            $invoiceIds = collect();
            
            // Reverse all payment allocations and collect invoice IDs
            foreach ($payment->allocations as $allocation) {
                if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                    $invoiceIds->push($allocation->invoiceItem->invoice_id);
                }
                $allocation->delete();
            }
            
            // Mark payment as reversed
            $payment->update([
                'reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);
            
            // Recalculate affected invoices (unique invoice IDs)
            $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds->unique())->get();
            
            foreach ($invoices as $invoice) {
                \App\Services\InvoiceService::recalc($invoice);
            }
            
            return back()->with('success', 'Payment reversed successfully. All allocations have been removed and invoices recalculated.');
        });
    }
    
    public function printReceipt(Payment $payment)
    {
        try {
            $payment->load(['student', 'invoice', 'paymentMethod', 'allocations.invoiceItem.votehead']);
            $pdf = $this->receiptService->generateReceipt($payment, ['save' => false]);
            
            // Return PDF in new window
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Receipt_' . $payment->receipt_number . '.pdf"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Receipt generation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Receipt generation failed: ' . $e->getMessage());
        }
    }

    public function viewReceipt(Payment $payment)
    {
        $payment->load([
            'student.classroom', 
            'invoice', 
            'paymentMethod', 
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);
        
        // Get school settings for receipt
        $schoolSettings = $this->getSchoolSettings();
        
        // Calculate allocation details with balances (same as ReceiptService)
        $allocations = $payment->allocations->map(function($allocation) {
            $item = $allocation->invoiceItem;
            $itemAmount = $item->amount ?? 0;
            $discountAmount = $item->discount_amount ?? 0;
            $allocatedAmount = $allocation->amount;
            $balanceBefore = $item->getBalance() + $allocatedAmount;
            $balanceAfter = $item->getBalance();
            
            return [
                'allocation' => $allocation,
                'invoice' => $item->invoice ?? null,
                'votehead' => $item->votehead ?? null,
                'item_amount' => $itemAmount,
                'discount_amount' => $discountAmount,
                'allocated_amount' => $allocatedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ];
        });
        
        // Calculate totals
        $totalBalanceBefore = $allocations->sum('balance_before');
        $totalBalanceAfter = $allocations->sum('balance_after');
        
        return view('finance.receipts.view', compact(
            'payment', 
            'schoolSettings',
            'allocations',
            'totalBalanceBefore',
            'totalBalanceAfter'
        ));
    }

    /**
     * Public receipt view (no authentication required, uses token instead of ID)
     * This route only accepts public_token (10 chars), not numeric IDs
     */
    public function publicViewReceipt(string $token)
    {
        // Explicitly find by public_token and validate length to prevent numeric ID access
        $payment = Payment::where('public_token', $token)
            ->whereRaw('LENGTH(public_token) = 10') // Ensure it's exactly 10 chars
            ->firstOrFail();
        
        $payment->load([
            'student.classroom', 
            'invoice', 
            'paymentMethod', 
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);
        
        // Get school settings for receipt
        $schoolSettings = $this->getSchoolSettings();
        
        // Calculate allocation details with balances (same as ReceiptService)
        $allocations = $payment->allocations->map(function($allocation) {
            $item = $allocation->invoiceItem;
            $itemAmount = $item->amount ?? 0;
            $discountAmount = $item->discount_amount ?? 0;
            $allocatedAmount = $allocation->amount;
            $balanceBefore = $item->getBalance() + $allocatedAmount;
            $balanceAfter = $item->getBalance();
            
            return [
                'allocation' => $allocation,
                'invoice' => $item->invoice ?? null,
                'votehead' => $item->votehead ?? null,
                'item_amount' => $itemAmount,
                'discount_amount' => $discountAmount,
                'allocated_amount' => $allocatedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ];
        });
        
        // Calculate totals
        $totalBalanceBefore = $allocations->sum('balance_before');
        $totalBalanceAfter = $allocations->sum('balance_after');
        
        return view('finance.receipts.public', compact(
            'payment', 
            'schoolSettings',
            'allocations',
            'totalBalanceBefore',
            'totalBalanceAfter'
        ));
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
            $settings = \Illuminate\Support\Facades\DB::table('settings')->whereIn('key', [
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
     * Initiate online payment
     */
    public function initiateOnline(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'gateway' => 'required|in:mpesa,stripe,paypal',
            'phone_number' => 'required_if:gateway,mpesa|string',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $invoice = Invoice::findOrFail($validated['invoice_id']);

        try {
            $options = [];
            if ($validated['gateway'] === 'mpesa' && isset($validated['phone_number'])) {
                $options['phone_number'] = $validated['phone_number'];
            }

            $transaction = $this->paymentService->initiatePayment(
                $student,
                $invoice,
                $validated['gateway'],
                $options
            );

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'message' => 'Payment initiated successfully. Please complete the payment.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function showTransaction($transaction)
    {
        $transaction = \App\Models\PaymentTransaction::findOrFail($transaction);
        return view('finance.payments.transaction', compact('transaction'));
    }

    public function verifyTransaction(Request $request, $transaction)
    {
        $transaction = \App\Models\PaymentTransaction::findOrFail($transaction);
        
        try {
            $status = $this->paymentService->verifyPayment($transaction);
            return response()->json(['status' => $status]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Normalize URL for local/production environments
     * Prevents double port numbers and ensures correct URL format
     */
    protected function normalizeUrl(string $url): string
    {
        // First, remove any double ports (e.g., :8000:8000)
        $url = preg_replace('/:(\d+):\1/', ':$1', $url);
        
        // Parse the URL
        $parsed = parse_url($url);
        
        if (!$parsed) {
            return $url;
        }
        
        $scheme = $parsed['scheme'] ?? request()->getScheme();
        $host = $parsed['host'] ?? request()->getHost();
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        
        // Extract port from host if present
        $portInHost = null;
        if (strpos($host, ':') !== false) {
            [$host, $portInHost] = explode(':', $host, 2);
        }
        
        // Determine which port to use
        $port = $portInHost ?? $parsed['port'] ?? null;
        $currentPort = request()->getPort();
        
        // For local development, use current port if not 80/443
        if (in_array($host, ['127.0.0.1', 'localhost']) && $currentPort && !in_array($currentPort, [80, 443])) {
            $port = $currentPort;
        }
        
        // Build URL
        $normalizedUrl = $scheme . '://' . $host;
        
        // Add port only if needed and not standard ports
        if ($port && !in_array($port, [80, 443])) {
            $normalizedUrl .= ':' . $port;
        }
        
        $normalizedUrl .= $path . $query . $fragment;
        
        // Final safety check: remove any remaining double ports
        $normalizedUrl = preg_replace('/:(\d+):\1/', ':$1', $normalizedUrl);
        
        return $normalizedUrl;
    }
}

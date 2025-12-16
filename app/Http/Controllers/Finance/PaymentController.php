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
            Log::warning('Payment notification failed: ' . $e->getMessage());
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
        $payment->load(['student.parentInfo', 'paymentMethod']);
        $student = $payment->student;
        
        // Get parent contact info
        $parent = $student->parentInfo ?? $student->parents()->first();
        $parentPhone = $parent->phone ?? $parent->mobile_phone ?? null;
        $parentEmail = $parent->email ?? null;
        
        if (!$parentPhone && !$parentEmail) {
            Log::info('No parent contact info found for payment notification', ['payment_id' => $payment->id]);
            return;
        }
        
        // Get or create payment receipt template
        $smsTemplate = CommunicationTemplate::firstOrCreate(
            ['code' => 'payment_receipt_sms'],
            [
                'title' => 'Payment Receipt SMS',
                'type' => 'sms',
                'subject' => 'Payment Receipt',
                'content' => 'Dear {{parent_name}}, Payment of Ksh {{amount}} received for {{student_name}} ({{admission_number}}). Receipt #{{receipt_number}}. View receipt: {{receipt_link}}',
            ]
        );
        
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
        $receiptLink = route('finance.payments.receipt.view', $payment);
        $variables = [
            'parent_name' => $parent->name ?? 'Parent',
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'amount' => number_format($payment->amount, 2),
            'receipt_number' => $payment->receipt_number,
            'transaction_code' => $payment->transaction_code,
            'payment_date' => $payment->payment_date->format('d M Y'),
            'receipt_link' => $receiptLink,
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
                $this->smsService->sendSMS($parentPhone, $smsMessage);
                
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id' => $parent->id ?? null,
                    'contact' => $parentPhone,
                    'channel' => 'sms',
                    'title' => $smsTemplate->subject ?? $smsTemplate->title,
                    'message' => $smsMessage,
                    'type' => 'sms',
                    'status' => 'sent',
                    'scope' => 'finance',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('SMS sending failed', ['error' => $e->getMessage(), 'payment_id' => $payment->id]);
            }
        }
        
        // Send Email with PDF attachment
        if ($parentEmail) {
            try {
                $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                
                // Generate PDF receipt
                $pdfPath = $this->receiptService->generateReceipt($payment, ['save' => true]);
                
                Mail::to($parentEmail)->send(new GenericMail(
                    $emailSubject,
                    $emailContent,
                    $pdfPath
                ));
                
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id' => $parent->id ?? null,
                    'contact' => $parentEmail,
                    'channel' => 'email',
                    'title' => $emailSubject,
                    'message' => $emailContent,
                    'type' => 'email',
                    'status' => 'sent',
                    'scope' => 'finance',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Email sending failed', ['error' => $e->getMessage(), 'payment_id' => $payment->id]);
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
        $payment->load(['student', 'invoice', 'paymentMethod', 'allocations.invoiceItem.votehead']);
        
        // Get school settings for receipt
        $schoolSettings = $this->getSchoolSettings();
        
        return view('finance.receipts.view', compact('payment', 'schoolSettings'));
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
}

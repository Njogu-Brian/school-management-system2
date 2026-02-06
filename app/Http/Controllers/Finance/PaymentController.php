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

    public function index(Request $request)
    {
        $query = Payment::with(['student.classroom', 'student.stream', 'paymentMethod', 'invoice'])
            ->whereHas('student', function($q) {
                $q->where('archive', 0)->where('is_alumni', false);
            })
            // Exclude swimming payments - they are managed separately in Swimming Management
            ->where('receipt_number', 'not like', 'SWIM-%')
            // Exclude soft-deleted payments
            ->whereNull('deleted_at');
            // Note: Reversed payments are now included by default - they can be filtered out using the status filter if needed
        
        // Apply filters
        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
        }
        
        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('classroom_id', $request->class_id);
            });
        }
        
        if ($request->filled('stream_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('stream_id', $request->stream_id);
            });
        }
        
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }
        
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }
        
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', '%' . $search . '%')
                  ->orWhere('transaction_code', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->filled('status')) {
            // Status filtering would need to be implemented based on payment status logic
            // For now, we'll skip this as it requires more complex logic
        }

        if ($request->filled('allocation_status')) {
            $status = $request->allocation_status;
            if ($status === 'unallocated') {
                $query->where(function ($q) {
                    $q->where('unallocated_amount', '>', 0)
                      ->orWhereRaw('amount > COALESCE(allocated_amount, 0)');
                });
            } elseif ($status === 'allocated') {
                $query->whereRaw('COALESCE(unallocated_amount, amount - COALESCE(allocated_amount, 0)) <= 0');
            }
        }

        $sort = $request->input('sort', 'payment_date_desc');
        if ($sort === 'amount_desc') {
            $query->orderByDesc('amount')->orderByDesc('payment_date');
        } else {
            $query->latest('payment_date');
        }
        
        $payments = $query->paginate(20)->appends($request->all());
        
        // Get filter options for the view
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        $streams = \App\Models\Academics\Stream::orderBy('name')->get();
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('name')->get();
        
        return view('finance.payments.index', compact('payments', 'classrooms', 'streams', 'paymentMethods'));
    }

    public function create(Request $request)
    {
        $bankAccounts = \App\Models\BankAccount::active()->get();
        $paymentMethods = \App\Models\PaymentMethod::active()->get();
        
        // Handle student_id and invoice_id from query parameters (when coming from invoice page)
        $studentId = $request->query('student_id');
        $invoiceId = $request->query('invoice_id');
        $student = null;
        $invoice = null;
        
        if ($studentId) {
            $student = Student::withAlumni()->find($studentId);
        }
        
        if ($invoiceId) {
            $invoice = \App\Models\Invoice::find($invoiceId);
            // If student not set but invoice exists, get student from invoice
            if (!$student && $invoice) {
                $student = $invoice->student;
            }
        }
        
        return view('finance.payments.create', compact('bankAccounts', 'paymentMethods', 'student', 'invoice'));
    }

    public function getStudentBalanceAndSiblings(Student $student)
    {
        $studentId = $student->id;
        $invoices = Invoice::where('student_id', $studentId)->get();
        
        // Get total outstanding balance (already handles balance brought forward correctly)
        $totalBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        
        // Get invoice balance (excluding balance brought forward items to show accurate breakdown)
        $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
        $invoiceBalance = $invoices->sum('balance');
        
        // Check if balance brought forward is in invoices
        $balanceBroughtForwardInInvoice = 0;
        if ($balanceBroughtForwardVotehead) {
            $balanceBroughtForwardInInvoice = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($studentId) {
                $q->where('student_id', $studentId)
                  ->where('status', '!=', 'reversed');
            })
            ->where('votehead_id', $balanceBroughtForwardVotehead->id)
            ->where('source', 'balance_brought_forward')
            ->get()
            ->sum(function($item) {
                $paid = $item->allocations()->sum('amount');
                return max(0, $item->amount - $paid);
            });
        }
        
        // Get balance brought forward from legacy data
        $balanceBroughtForward = \App\Services\StudentBalanceService::getBalanceBroughtForward($student);
        
        // If balance brought forward is in invoices, show it separately in breakdown
        // Otherwise, it's already included in total but not in invoice balance
        $displayBalanceBroughtForward = $balanceBroughtForwardInInvoice > 0 ? $balanceBroughtForwardInInvoice : $balanceBroughtForward;
        
        $unpaidInvoices = $invoices->where('balance', '>', 0)->count();
        $partialInvoices = $invoices->where('balance', '>', 0)->where('balance', '<', $invoices->sum('total'))->count();
        
        // Get siblings (excluding current student)
        $siblings = $student->family 
            ? $student->family->students()->where('id', '!=', $studentId)->get()->map(function($sibling) {
                $siblingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($sibling);
                return [
                    'id' => $sibling->id,
                    'name' => $sibling->first_name . ' ' . $sibling->last_name,
                    'admission_number' => $sibling->admission_number,
                    'balance' => $siblingBalance,
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
                'invoice_balance' => $invoiceBalance,
                'balance_brought_forward' => $displayBalanceBroughtForward,
                'unpaid_invoices' => $unpaidInvoices,
                'partial_invoices' => $partialInvoices,
            ],
            'siblings' => $siblings,
        ]);
    }

    public function store(Request $request)
    {
        try {
        // For shared payments, transaction_code can be the same for different students
        // For single payments, transaction_code must be unique per student
        $transactionCodeRule = 'required|string';
        if (!($request->shared_payment ?? false)) {
            // Only check uniqueness per student for non-shared payments
            $transactionCodeRule .= '|unique:payments,transaction_code,NULL,id,student_id,' . $request->student_id;
        }
        
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payer_name' => 'nullable|string|max:255',
            'payer_type' => 'nullable|in:parent,sponsor,student,other',
            'narration' => 'nullable|string',
            'transaction_code' => $transactionCodeRule, // Transaction code must be unique per student (or shared for siblings)
            'auto_allocate' => 'nullable|boolean',
            'allocations' => 'nullable|array', // Manual allocations
            'allocations.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'shared_payment' => 'nullable|boolean', // For payment sharing among siblings
            'shared_students' => 'nullable|array', // Array of student IDs for shared payment
            'shared_amounts' => 'nullable|array', // Array of amounts for each student
        ]);

        $student = Student::withAlumni()->findOrFail($validated['student_id']);

        // Check for overpayment warning
        $invoice = isset($validated['invoice_id']) && $validated['invoice_id'] ? \App\Models\Invoice::find($validated['invoice_id']) : null;
        
        // Calculate student balance from invoices including balance brought forward
        $studentInvoices = Invoice::where('student_id', $student->id)->get();
        $invoiceBalance = $invoice ? $invoice->balance : $studentInvoices->sum('balance');
        
        // Get total outstanding balance including balance brought forward
        $balance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        
        // For alumni or archived students, enforce balance limit (no overpayment allowed)
        $isAlumniOrArchived = $student->is_alumni || $student->archive;
        $isOverpayment = $validated['amount'] > $balance;
        
        if ($isAlumniOrArchived && $isOverpayment) {
            return back()
                ->withInput()
                ->with('error', "Cannot accept overpayment for " . ($student->is_alumni ? 'alumni' : 'archived') . " students. Maximum payment allowed is Ksh " . number_format($balance, 2) . " (outstanding balance).");
        }
        
        if ($isOverpayment && !($request->has('confirm_overpayment') && $request->confirm_overpayment)) {
            return back()
                ->withInput()
                ->with('warning', "Warning: Payment amount (Ksh " . number_format($validated['amount'], 2) . ") exceeds balance (Ksh " . number_format($balance, 2) . "). Overpayment of Ksh " . number_format($validated['amount'] - $balance, 2) . " will be carried forward.")
                ->with('show_overpayment_confirm', true);
        }

        $createdPayment = null;
        $createdPaymentIds = [];

        DB::transaction(function () use ($validated, $student, $isOverpayment, &$createdPayment, &$createdPaymentIds) {
            // Handle payment sharing among siblings
            if ($validated['shared_payment'] ?? false && !empty($validated['shared_students'])) {
                $sharedStudents = $validated['shared_students'];
                $sharedAmounts = $validated['shared_amounts'] ?? [];
                $totalShared = array_sum($sharedAmounts);
                
                // Validate total shared equals payment amount
                if (abs($totalShared - $validated['amount']) > 0.01) {
                    throw new \Exception('Total shared amounts must equal payment amount.');
                }
                
                // Use same transaction code for all sibling payments
                $sharedTransactionCode = $validated['transaction_code'];
                $sharedReceiptNumber = $this->generateSharedReceiptNumber();
                
                // Create payments for each sibling
                $createdPayments = [];
                foreach ($sharedStudents as $index => $siblingId) {
                    $sibling = Student::findOrFail($siblingId);
                    $siblingAmount = $sharedAmounts[$index] ?? 0;
                    
                    if ($siblingAmount > 0) {
                        // Use shared receipt number for the group, and a unique receipt_number per payment
                        $receiptNumber = $this->ensureUniqueReceiptNumber($sharedReceiptNumber . '-S' . $siblingId, $siblingId);
                        
                        $payment = Payment::create([
                            'student_id' => $siblingId,
                            'family_id' => $sibling->family_id,
                            'invoice_id' => null, // Will be auto-allocated
                            'amount' => $siblingAmount,
                            'payment_method_id' => $validated['payment_method_id'],
                            'payer_name' => $validated['payer_name'],
                            'payer_type' => $validated['payer_type'],
                            'narration' => $validated['narration'],
                            'transaction_code' => $sharedTransactionCode, // Same transaction code for all siblings
                            'receipt_number' => $receiptNumber, // Unique per payment
                            'shared_receipt_number' => $sharedReceiptNumber, // Shared across siblings
                            'payment_date' => $validated['payment_date'],
                            // receipt_date is set automatically in Payment model
                        ]);
                        
                        // Auto-allocate for sibling
                        try {
                            if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                                $this->allocationService->autoAllocateWithInstallments($payment);
                            } elseif (method_exists($this->allocationService, 'autoAllocate')) {
                                $this->allocationService->autoAllocate($payment);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Sibling auto-allocation failed: ' . $e->getMessage());
                            // Continue - payment is still created
                        }
                        
                        // Generate receipt for this sibling payment
                        try {
                            $this->receiptService->generateReceipt($payment, ['save' => true]);
                        } catch (\Exception $e) {
                            Log::warning('Receipt generation failed for sibling payment', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        // Send notification for this sibling payment
                        try {
                            $this->sendPaymentNotifications($payment);
                        } catch (\Exception $e) {
                            Log::warning('Notification failed for sibling payment', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage()
                            ]);
                            flash_sms_credit_warning($e);
                        }
                        
                        $createdPayments[] = $payment;
                        
                        // Store first payment for return value
                        if ($index === 0) {
                            $createdPayment = $payment;
                        }
                        $createdPaymentIds[] = $payment->id;
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
                if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                    $this->allocationService->autoAllocateWithInstallments($payment);
                } else {
                    $this->allocationService->autoAllocate($payment);
                }
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
                $createdPaymentIds[] = $payment->id;
            }
        });

        // Auto-match to bank statement transaction if transaction code matches
        try {
            $this->autoMatchToBankStatement($createdPayment);
        } catch (\Exception $e) {
            Log::warning('Auto-match to bank statement failed', [
                'payment_id' => $createdPayment->id ?? null,
                'transaction_code' => $createdPayment->transaction_code,
                'error' => $e->getMessage(),
            ]);
        }

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
            flash_sms_credit_warning($e);
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
        $redirect = redirect()
            ->route('finance.payments.index')
            ->with('success', 'Payment recorded successfully.')
            ->with('payment_id', $createdPayment->id);
        if (!empty($createdPaymentIds)) {
            $redirect->with('receipt_ids', $createdPaymentIds);
        }
        return $redirect;
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
    
    /**
     * Send payment notifications (SMS, Email, WhatsApp)
     * Made public so it can be called from other controllers (e.g., BankStatementController)
     */
    protected function getProfileUpdateLinkForStudent(Student $student): ?string
    {
        $student->loadMissing('family.updateLink');
        
        if (!$student->family_id) {
            $family = \App\Models\Family::create([
                'guardian_name' => $student->full_name
                    ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
            ]);
            $student->update(['family_id' => $family->id]);
            $student->load('family.updateLink');
        }
        
        if (!$student->family) {
            return null;
        }
        
        if (!$student->family->updateLink) {
            \App\Models\FamilyUpdateLink::create([
                'family_id' => $student->family->id,
                'is_active' => true,
            ]);
            $student->family->refresh();
            $student->family->load('updateLink');
        }
        
        $updateLink = $student->family->updateLink;
        if (!$updateLink || !$updateLink->is_active) {
            return null;
        }
        
        return route('family-update.form', $updateLink->token);
    }

    public function sendPaymentNotifications(Payment $payment)
    {
        // Check if this is a swimming payment - if so, use swimming-specific notification
        $isSwimmingPayment = strpos($payment->receipt_number ?? '', 'SWIM-') === 0 || 
            strpos($payment->narration ?? '', 'Swimming') !== false ||
            strpos($payment->narration ?? '', '(Swimming)') !== false;
        
        if ($isSwimmingPayment) {
            $this->sendSwimmingPaymentNotifications($payment);
            return;
        }
        
        $payment->load(['student.parent', 'paymentMethod']);
        $student = $payment->student;
        $profileUpdateLink = $this->getProfileUpdateLinkForStudent($student);
        
        // Get parent contact info
        $parent = $student->parent;
        
        if (!$parent) {
            Log::info('No parent found for payment notification', ['payment_id' => $payment->id, 'student_id' => $student->id]);
            return;
        }
        
        // Get primary contact phone and email from ParentInfo model
        $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null;
        $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null;
        
        if (!$parentPhone && !$parentEmail) {
            Log::info('No parent contact info found for payment notification', ['payment_id' => $payment->id]);
            return;
        }
        
        // Use templates - try both old and new template codes for compatibility
        $smsTemplate = CommunicationTemplate::where('code', 'payment_receipt_sms')
            ->orWhere('code', 'finance_payment_received_sms')
            ->first();
        $emailTemplate = CommunicationTemplate::where('code', 'payment_receipt_email')
            ->orWhere('code', 'finance_payment_received_email')
            ->first();
        
        // Fallback: create templates if they don't exist
        if (!$smsTemplate) {
            $smsTemplate = CommunicationTemplate::firstOrCreate(
                ['code' => 'payment_receipt_sms'],
                [
                    'title' => 'Payment Receipt SMS',
                    'type' => 'sms',
                    'subject' => null,
                    'content' => "{{greeting}},\n\nPayment of {{amount}} received for {{student_name}} ({{admission_number}}) on {{payment_date}}.\nReceipt: {{receipt_number}}\nView receipt: {{receipt_link}}\nUpdate profile: {{profile_update_link}}\n\nThank you.\n{{school_name}}",
                ]
            );
        }
        
        if (!$emailTemplate) {
            $emailTemplate = CommunicationTemplate::firstOrCreate(
                ['code' => 'payment_receipt_email'],
                [
                    'title' => 'Payment Receipt Email',
                    'type' => 'email',
                    'subject' => 'Payment Receipt - {{receipt_number}}',
                    'content' => "<p>{{greeting}},</p><p>We have received a payment of <strong>{{amount}}</strong> for <strong>{{student_name}}</strong> (Admission: {{admission_number}}) on {{payment_date}}.</p><p><strong>Receipt Number:</strong> {{receipt_number}}<br><strong>Transaction Code:</strong> {{transaction_code}}</p><p><a href=\"{{receipt_link}}\" style=\"display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;\">View Receipt</a></p><p><a href=\"{{profile_update_link}}\" style=\"display:inline-block;padding:8px 14px;background:#6c757d;color:#fff;text-decoration:none;border-radius:6px;\">Update Parent Profile</a></p><p>Thank you for your continued support.<br>{{school_name}}</p>",
                ]
            );
        }
        
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
        
        // Get parent name - return null if not found (not 'Parent')
        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? null;
        
        // Create greeting: "Dear Parent" when name is unknown, "Dear [Name]" when name is known
        $greeting = $parentName ? "Dear {$parentName}" : "Dear Parent";
        
        // Calculate outstanding balance for the student (after this payment)
        // Refresh payment to ensure allocations are loaded
        $payment->refresh();
        
        // Optimize: Only recalculate invoices that were affected by this payment
        // Get invoices that have allocations from this payment
        // payment_allocations -> invoice_items -> invoices (need to join through invoice_items)
        $affectedInvoiceIds = $payment->allocations()
            ->join('invoice_items', 'payment_allocations.invoice_item_id', '=', 'invoice_items.id')
            ->pluck('invoice_items.invoice_id')
            ->unique()
            ->filter();
        
        if ($affectedInvoiceIds->isNotEmpty()) {
            // Only recalculate affected invoices
            $studentInvoices = \App\Models\Invoice::whereIn('id', $affectedInvoiceIds)
                ->orWhere('student_id', $student->id) // Also include all student invoices for balance calculation
                ->get();
            
            // Enable auto-allocation for invoice recalculation (to auto-allocate unallocated payments)
            app()->instance('auto_allocating', true);
            
            // Recalculate only affected invoices
            foreach ($studentInvoices->whereIn('id', $affectedInvoiceIds) as $invoice) {
                try {
                    if (class_exists(\App\Services\InvoiceService::class) && method_exists(\App\Services\InvoiceService::class, 'recalc')) {
                        \App\Services\InvoiceService::recalc($invoice);
                    } elseif (method_exists($invoice, 'recalculate')) {
                        $invoice->recalculate();
                    }
                } catch (\Exception $e) {
                    Log::debug('Invoice recalculation in notification: ' . $e->getMessage());
                }
            }
            
            // Disable auto-allocation after recalculation
            app()->instance('auto_allocating', false);
            
            // Refresh only affected invoices
            $studentInvoices->whereIn('id', $affectedInvoiceIds)->each->refresh();
        } else {
            // No allocations yet, get all invoices for balance calculation
            $studentInvoices = \App\Models\Invoice::where('student_id', $student->id)->get();
        }
        
        // Calculate total outstanding balance after payment (including balance brought forward)
        $outstandingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        
        // Refresh payment to get latest unallocated_amount
        $payment->refresh();
        $carriedForward = $payment->unallocated_amount ?? 0;
        
        // Get school name for template
        $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
        
        $displayReceiptNumber = $payment->shared_receipt_number ?? $payment->receipt_number;
        $variables = [
            'parent_name' => $parentName ?? 'Parent', // Keep for backward compatibility
            'greeting' => $greeting, // New greeting variable: "Dear Parent" or "Dear [Name]"
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'amount' => 'Ksh ' . number_format($payment->amount, 2),
            'receipt_number' => $displayReceiptNumber,
            'transaction_code' => $payment->transaction_code,
            'payment_date' => $payment->payment_date->format('d M Y'),
            'receipt_link' => $receiptLink,
            'finance_portal_link' => $receiptLink, // Alias for seeder template compatibility
            'profile_update_link' => $profileUpdateLink,
            'outstanding_amount' => 'Ksh ' . number_format($outstandingBalance, 2),
            'carried_forward' => number_format($carriedForward, 2),
            'school_name' => $schoolName,
        ];
        
        // Replace placeholders
        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };
        
        // Send SMS using finance sender ID (no profile update link in SMS; kept in receipt only)
        if ($parentPhone) {
            try {
                $smsVariables = $variables;
                $smsVariables['profile_update_link'] = '';
                $smsMessage = $replacePlaceholders($smsTemplate->content, $smsVariables);
                // Remove any remaining "Update profile:" line if template had it
                $smsMessage = preg_replace('/\n?Update profile:.*$/m', '', $smsMessage);
                
                // Get finance sender ID for payment notifications
                $smsService = app(\App\Services\SMSService::class);
                $financeSenderId = $smsService->getFinanceSenderId();
                
                Log::info('Attempting to send payment SMS', [
                    'payment_id' => $payment->id,
                    'phone' => $parentPhone,
                    'template_code' => $smsTemplate->code,
                    'message_length' => strlen($smsMessage),
                    'sender_id' => $financeSenderId
                ]);
                
                $this->commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, $smsTemplate->subject ?? $smsTemplate->title, $financeSenderId, $payment->id);
                
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
                    'template_code' => $smsTemplate->code ?? 'unknown',
                    'trace' => $e->getTraceAsString()
                ]);
                flash_sms_credit_warning($e);
                // Don't throw - allow email to still be sent
            }
        } else {
            Log::info('Payment SMS skipped - no parent phone', ['payment_id' => $payment->id, 'student_id' => $student->id]);
        }
        
        // Send Email with PDF attachment (queue PDF generation for better performance)
        if ($parentEmail) {
            try {
                $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                if ($profileUpdateLink && strpos($emailContent, $profileUpdateLink) === false) {
                    $emailContent .= "<p><a href=\"{$profileUpdateLink}\" style=\"display:inline-block;padding:8px 14px;background:#6c757d;color:#fff;text-decoration:none;border-radius:6px;\">Update Parent Profile</a></p>";
                }
                
                Log::info('Attempting to send payment email', [
                    'payment_id' => $payment->id,
                    'email' => $parentEmail,
                    'template_code' => $emailTemplate->code
                ]);
                
                // Generate PDF receipt (this is still synchronous but optimized)
                // TODO: Consider queuing PDF generation for bulk operations
                $pdfPath = $this->receiptService->generateReceipt($payment, ['save' => true]);
                
                // Use CommunicationService to send email (handles logging automatically)
                $this->commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent, $pdfPath);
                
                Log::info('Payment email sent successfully', [
                    'payment_id' => $payment->id, 
                    'email' => $parentEmail,
                    'pdf_path' => $pdfPath
                ]);
            } catch (\Exception $e) {
                Log::error('Payment email sending failed', [
                    'error' => $e->getMessage(), 
                    'payment_id' => $payment->id, 
                    'email' => $parentEmail,
                    'template_code' => $emailTemplate->code ?? 'unknown',
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::info('Payment email skipped - no parent email', ['payment_id' => $payment->id, 'student_id' => $student->id]);
        }
        
        // Send WhatsApp notification
        // Get WhatsApp number with fallback to phone number (prioritize father/mother)
        // Never send fee-related communications to guardian; guardians are reached via manual number entry only
        $whatsappPhone = !empty($parent->father_whatsapp) ? $parent->father_whatsapp 
            : (!empty($parent->mother_whatsapp) ? $parent->mother_whatsapp 
            : (!empty($parent->father_phone) ? $parent->father_phone 
            : (!empty($parent->mother_phone) ? $parent->mother_phone : null)));
        
        if ($whatsappPhone) {
            try {
                // Get WhatsApp template
                $whatsappTemplate = CommunicationTemplate::where('code', 'payment_receipt_whatsapp')
                    ->orWhere('code', 'finance_payment_received_whatsapp')
                    ->first();
                
                if (!$whatsappTemplate) {
                    $whatsappTemplate = CommunicationTemplate::firstOrCreate(
                        ['code' => 'payment_receipt_whatsapp'],
                        [
                            'title' => 'Payment Receipt WhatsApp',
                            'type' => 'whatsapp',
                            'subject' => null,
                            'content' => "{{greeting}},\n\nPayment of {{amount}} received for {{student_name}} ({{admission_number}}) on {{payment_date}}.\nReceipt: {{receipt_number}}\nView receipt: {{receipt_link}}\nUpdate profile: {{profile_update_link}}\n\nThank you.\n{{school_name}}",
                        ]
                    );
                }
                
                $whatsappMessage = $replacePlaceholders($whatsappTemplate->content, $variables);
                if ($profileUpdateLink && strpos($whatsappMessage, $profileUpdateLink) === false) {
                    $whatsappMessage .= "\nUpdate profile: {$profileUpdateLink}";
                }
                
                $whatsappService = app(\App\Services\WhatsAppService::class);
                $response = $whatsappService->sendMessage($whatsappPhone, $whatsappMessage);
                
                $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';
                
                // Log WhatsApp communication
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id'   => $parent->id ?? null,
                    'contact'        => $whatsappPhone,
                    'channel'        => 'whatsapp',
                    'title'          => $whatsappTemplate->subject ?? $whatsappTemplate->title,
                    'message'        => $whatsappMessage,
                    'type'           => 'whatsapp',
                    'status'         => $status,
                    'response'       => $response,
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'payment_id'     => $payment->id,
                    'provider_id'    => data_get($response, 'body.data.id') 
                                        ?? data_get($response, 'body.data.message.id')
                                        ?? data_get($response, 'body.messageId')
                                        ?? data_get($response, 'body.id'),
                    'provider_status'=> data_get($response, 'body.status') ?? data_get($response, 'status'),
                ]);
                
                if ($status === 'sent') {
                    Log::info('Payment WhatsApp sent successfully', [
                        'payment_id' => $payment->id,
                        'phone' => $whatsappPhone,
                    ]);
                } else {
                    Log::warning('Payment WhatsApp sending failed', [
                        'payment_id' => $payment->id,
                        'phone' => $whatsappPhone,
                        'response' => $response,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Payment WhatsApp sending failed', [
                    'error' => $e->getMessage(),
                    'payment_id' => $payment->id,
                    'phone' => $whatsappPhone,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Log failed WhatsApp attempt
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id'   => $parent->id ?? null,
                    'contact'        => $whatsappPhone,
                    'channel'        => 'whatsapp',
                    'title'          => 'Payment Receipt',
                    'message'        => $whatsappMessage ?? 'Payment notification',
                    'type'           => 'whatsapp',
                    'status'         => 'failed',
                    'response'       => ['error' => $e->getMessage()],
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'payment_id'     => $payment->id,
                ]);
            }
        } else {
            Log::info('Payment WhatsApp skipped - no parent WhatsApp/phone', ['payment_id' => $payment->id, 'student_id' => $student->id]);
        }
    }

    /**
     * Send swimming payment notifications
     */
    protected function sendSwimmingPaymentNotifications(Payment $payment): void
    {
        $payment->load(['student.parent', 'paymentMethod']);
        $student = $payment->student;
        $profileUpdateLink = $this->getProfileUpdateLinkForStudent($student);
        
        if (!$student) {
            Log::info('No student found for swimming payment notification', ['payment_id' => $payment->id]);
            return;
        }
        
        $parent = $student->parent;
        if (!$parent) {
            Log::info('No parent found for swimming payment notification', ['payment_id' => $payment->id, 'student_id' => $student->id]);
            return;
        }
        
        // Get parent contact info
        $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null;
        $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null;
        
        if (!$parentPhone && !$parentEmail) {
            Log::info('No parent contact info found for swimming payment notification', ['payment_id' => $payment->id]);
            return;
        }
        
        // Get swimming wallet balance
        $wallet = \App\Models\SwimmingWallet::getOrCreateForStudent($student->id);
        $walletBalance = $wallet->balance;
        
        // Prepare message
        $studentName = $student->first_name . ' ' . $student->last_name;
        $amountFormatted = number_format($payment->amount, 2);
        $walletBalanceFormatted = number_format($walletBalance, 2);
        
        // SMS message
        $smsMessage = "Dear Parent,\n\n";
        $smsMessage .= "Swimming payment of KES {$amountFormatted} for {$studentName} ({$student->admission_number}) has been received.\n\n";
        $smsMessage .= "Receipt: {$payment->receipt_number}\n";
        $smsMessage .= "Date: " . $payment->payment_date->format('d M Y H:i') . "\n\n";
        $smsMessage .= "Swimming Wallet Balance: KES {$walletBalanceFormatted}\n\n";
        $receiptLink = null;
        if (!$payment->public_token) {
            $payment->public_token = \App\Models\Payment::generatePublicToken();
            $payment->save();
        }
        try {
            $receiptLink = url('/receipt/' . $payment->public_token);
            $receiptLink = $this->normalizeUrl($receiptLink);
        } catch (\Exception $e) {
            $receiptLink = null;
        }
        
        if ($receiptLink) {
            $smsMessage .= "View receipt: {$receiptLink}\n";
        }
        // Profile update link not included in SMS (kept in receipt only)
        $smsMessage .= "Thank you!\nRoyal Kings School";
        
        // Email message
        $emailSubject = "Swimming Payment Confirmation - {$payment->receipt_number}";
        $emailContent = "<p>Dear Parent,</p>";
        $emailContent .= "<p>Swimming payment of <strong>KES {$amountFormatted}</strong> for <strong>{$studentName}</strong> (Admission: {$student->admission_number}) has been received.</p>";
        $emailContent .= "<p><strong>Receipt Number:</strong> {$payment->receipt_number}<br>";
        $emailContent .= "<strong>Transaction Code:</strong> " . ($payment->transaction_code ?? 'N/A') . "<br>";
        $emailContent .= "<strong>Payment Date:</strong> " . $payment->payment_date->format('d M Y H:i') . "</p>";
        $emailContent .= "<p><strong>Swimming Wallet Balance:</strong> KES {$walletBalanceFormatted}</p>";
        if ($receiptLink) {
            $emailContent .= "<p><a href=\"{$receiptLink}\" style=\"display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;\">View Receipt</a></p>";
        }
        if ($profileUpdateLink) {
            $emailContent .= "<p><a href=\"{$profileUpdateLink}\" style=\"display:inline-block;padding:8px 14px;background:#6c757d;color:#fff;text-decoration:none;border-radius:6px;\">Update Parent Profile</a></p>";
        }
        $emailContent .= "<p>Thank you for your continued support.</p>";
        $emailContent .= "<p>Royal Kings School</p>";
        
        // Send SMS
        if ($parentPhone) {
            try {
                $this->commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, 'Swimming Payment Confirmation', 'RKS_FINANCE', $payment->id);
                Log::info('Swimming payment SMS sent', ['payment_id' => $payment->id, 'phone' => $parentPhone]);
            } catch (\Exception $e) {
                Log::error('Failed to send swimming payment SMS', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                flash_sms_credit_warning($e);
            }
        }
        
        // Send Email
        if ($parentEmail) {
            try {
                $this->commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent);
                Log::info('Swimming payment email sent', ['payment_id' => $payment->id, 'email' => $parentEmail]);
            } catch (\Exception $e) {
                Log::error('Failed to send swimming payment email', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
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
        
        // Check if this payment is part of a shared transaction
        $sharedInfo = $this->getSharedTransactionInfo($payment);
        $siblings = collect();
        if ($payment->student && $payment->student->family_id) {
            $siblings = Student::where('family_id', $payment->student->family_id)
                ->where('id', '!=', $payment->student_id)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->limit(10)
                ->get();
        }
        
        return view('finance.payments.show', compact('payment', 'sharedInfo', 'siblings'));
    }
    
    /**
     * Get shared transaction information for a payment
     */
    protected function getSharedTransactionInfo(Payment $payment): ?array
    {
        if (!$payment->transaction_code) {
            return null;
        }
        $sharedReceiptNumber = $payment->shared_receipt_number;
        $allPaymentsQuery = Payment::with('student');
        if ($sharedReceiptNumber) {
            $allPaymentsQuery->where('shared_receipt_number', $sharedReceiptNumber);
        } else {
            $allPaymentsQuery->where('transaction_code', $payment->transaction_code);
        }
        $allPayments = $allPaymentsQuery->get();
        $siblingPayments = $allPayments->where('id', '!=', $payment->id)->values();
        
        // Find the source bank statement transaction
        $bankTransaction = \App\Models\BankStatementTransaction::where('reference_number', $payment->transaction_code)
            ->where('is_shared', true)
            ->whereNotNull('shared_allocations')
            ->first();
        
        if (!$bankTransaction && $allPayments->count() <= 1) {
            return null;
        }
        
        if (!$bankTransaction) {
            // If no bank transaction found, construct from payments
            $sharedAllocations = $allPayments->map(function($p) {
                return [
                    'student_id' => $p->student_id,
                    'amount' => (float) $p->amount,
                ];
            })->toArray();
            
            return [
                'is_shared' => true,
                'bank_transaction' => null,
                'sibling_payments' => $siblingPayments,
                'shared_allocations' => $sharedAllocations,
                'total_amount' => $allPayments->sum('amount'),
                'shared_receipt_number' => $sharedReceiptNumber,
            ];
        }
        
        return [
            'is_shared' => true,
            'bank_transaction' => $bankTransaction,
            'sibling_payments' => $siblingPayments,
            'shared_allocations' => $bankTransaction->shared_allocations ?? [],
            'total_amount' => $bankTransaction->amount,
            'shared_receipt_number' => $sharedReceiptNumber,
        ];
    }
    
    /**
     * Update shared allocations for a payment
     */
    public function updateSharedAllocations(Request $request, Payment $payment)
    {
        // Authorization check
        $this->authorize('editSharedAllocations', $payment);
        
        // Optimistic locking check
        if ($request->has('version') && $payment->version != $request->version) {
            return back()->with('error', 
                'This payment was modified by another user. Please refresh the page and try again.'
            );
        }
        
        // Prevent editing if payment is reversed
        if ($payment->reversed) {
            return back()->with('error', 'Cannot edit allocations for a reversed payment.');
        }
        
        // Check if any group payments are reversed
        $sharedReceiptNumber = $payment->shared_receipt_number;
        $reversedGroupPayments = Payment::where(function ($q) use ($payment, $sharedReceiptNumber) {
                if ($sharedReceiptNumber) {
                    $q->where('shared_receipt_number', $sharedReceiptNumber);
                } else {
                    $q->where('transaction_code', $payment->transaction_code);
                }
            })
            ->where('reversed', true)
            ->exists();
        
        if ($reversedGroupPayments) {
            return back()->with('error', 
                'Cannot edit allocations: One or more sibling payments have been reversed.'
            );
        }
        
        $validated = $request->validate([
            'version' => 'nullable|integer',
            'allocations' => 'required|array|min:1',
            'allocations.*.student_id' => 'required|exists:students,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);
        
        // Filter out allocations with 0 or empty amounts
        $activeAllocations = array_filter($validated['allocations'], function($allocation) {
            $amount = $allocation['amount'] ?? 0;
            return !empty($amount) && (float)$amount > 0;
        });
        
        if (empty($activeAllocations)) {
            return redirect()->back()
                ->withErrors(['allocations' => 'At least one sibling must have an amount greater than 0']);
        }
        
        // Re-index the array
        $activeAllocations = array_values($activeAllocations);
        
        // Get shared transaction info
        $sharedInfo = $this->getSharedTransactionInfo($payment);
        if (!$sharedInfo || !$sharedInfo['is_shared']) {
            return redirect()->back()
                ->with('error', 'This payment is not part of a shared transaction.');
        }
        
        $totalAmount = array_sum(array_column($activeAllocations, 'amount'));
        $expectedTotal = $sharedInfo['total_amount'];
        
        if (abs($totalAmount - $expectedTotal) > 0.01) {
            return redirect()->back()
                ->withErrors(['allocations' => 'Total allocation amount must equal transaction amount. Current total: Ksh ' . number_format($totalAmount, 2) . ', Expected: Ksh ' . number_format($expectedTotal, 2)]);
        }
        
        // Store old allocations for audit log
        $oldAllocations = $sharedInfo['shared_allocations'] ?? [];
        
        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $activeAllocations, $sharedInfo, $oldAllocations) {
            // Update bank statement transaction if it exists to keep in sync
            if ($sharedInfo['bank_transaction']) {
                $sharedInfo['bank_transaction']->update([
                    'shared_allocations' => $activeAllocations,
                ]);
                $sharedInfo['bank_transaction']->increment('version');
            }

            $sharedReceiptNumber = $sharedInfo['shared_receipt_number'] ?? $payment->shared_receipt_number;
            if (!$sharedReceiptNumber) {
                $sharedReceiptNumber = $this->generateSharedReceiptNumber();
            }
            
            // Update all sibling payments and allow new siblings
            $allPayments = collect([$payment])->merge($sharedInfo['sibling_payments']);
            $paymentsByStudent = $allPayments->keyBy('student_id');
            $activeStudentIds = collect($activeAllocations)->pluck('student_id')->toArray();
            $paymentsToNotify = collect();
            
            foreach ($activeAllocations as $allocation) {
                $studentId = $allocation['student_id'];
                $newAmount = (float) $allocation['amount'];
                $siblingPayment = $paymentsByStudent->get($studentId);
                
                if ($siblingPayment) {
                    $oldAmount = (float) $siblingPayment->amount;
                    $updates = [];
                    if (!$siblingPayment->shared_receipt_number) {
                        $updates['shared_receipt_number'] = $sharedReceiptNumber;
                    }
                    if (abs($oldAmount - $newAmount) > 0.01) {
                        $updates['amount'] = $newAmount;
                    }
                    if (!empty($updates)) {
                        $siblingPayment->update($updates);
                    }
                    
                    if (abs($oldAmount - $newAmount) > 0.01) {
                        // If amount decreased, deallocate excess (FIFO - oldest allocations first)
                        if ($newAmount < $oldAmount) {
                            $excess = $oldAmount - $newAmount;
                            $remaining = $excess;
                            $affectedInvoices = collect();
                            
                            $allocations = \App\Models\PaymentAllocation::where('payment_id', $siblingPayment->id)
                                ->with('invoiceItem.invoice')
                                ->orderBy('allocated_at', 'asc')
                                ->get();
                            
                            foreach ($allocations as $allocationRecord) {
                                if ($remaining <= 0) {
                                    break;
                                }
                                
                                $allocationAmount = (float)$allocationRecord->amount;
                                $invoice = $allocationRecord->invoiceItem->invoice;
                                
                                if ($allocationAmount <= $remaining) {
                                    $remaining -= $allocationAmount;
                                    $allocationRecord->delete();
                                    
                                    if ($invoice && !$affectedInvoices->contains('id', $invoice->id)) {
                                        $affectedInvoices->push($invoice);
                                    }
                                } else {
                                    $newAllocationAmount = $allocationAmount - $remaining;
                                    $allocationRecord->update(['amount' => $newAllocationAmount]);
                                    $remaining = 0;
                                    
                                    if ($invoice && !$affectedInvoices->contains('id', $invoice->id)) {
                                        $affectedInvoices->push($invoice);
                                    }
                                }
                            }
                            
                            foreach ($affectedInvoices as $invoice) {
                                \App\Services\InvoiceService::recalc($invoice);
                            }
                        } else {
                            // Amount increased - allocate additional amount
                            try {
                                if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                                    $this->allocationService->autoAllocateWithInstallments($siblingPayment, $siblingPayment->student_id);
                                } else {
                                    $this->allocationService->autoAllocate($siblingPayment, $siblingPayment->student_id);
                                }
                            } catch (\Exception $e) {
                                \Log::warning('Auto-allocation failed for increased shared payment', [
                                    'payment_id' => $siblingPayment->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        $siblingPayment->updateAllocationTotals();
                        $paymentsToNotify->push($siblingPayment);
                    }
                    
                    if ($siblingPayment->receipt) {
                        try {
                            $receiptService = app(\App\Services\ReceiptService::class);
                            $receiptService->generateReceipt($siblingPayment->fresh(), ['save' => true, 'regenerate' => true]);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to regenerate receipt after allocation update', [
                                'payment_id' => $siblingPayment->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } else {
                    $student = Student::withAlumni()->findOrFail($studentId);
                    $receiptNumber = $this->ensureUniqueReceiptNumber($sharedReceiptNumber . '-S' . $student->id, $student->id);
                    $newPayment = Payment::create([
                        'student_id' => $student->id,
                        'family_id' => $student->family_id,
                        'amount' => $newAmount,
                        'payment_method_id' => $payment->payment_method_id,
                        'payment_date' => $payment->payment_date,
                        'transaction_code' => $payment->transaction_code,
                        'receipt_number' => $receiptNumber,
                        'shared_receipt_number' => $sharedReceiptNumber,
                        'payer_name' => $payment->payer_name,
                        'payer_type' => $payment->payer_type,
                        'narration' => "Shared from {$payment->student->full_name} ({$payment->student->admission_number}) - Reshared payment",
                    ]);
                    
                    try {
                        if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                            $this->allocationService->autoAllocateWithInstallments($newPayment, $student->id);
                        } else {
                            $this->allocationService->autoAllocate($newPayment, $student->id);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Auto-allocation failed for new shared payment', [
                            'payment_id' => $newPayment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    try {
                        $this->receiptService->generateReceipt($newPayment->fresh(), ['save' => true]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to generate receipt for new shared payment', [
                            'payment_id' => $newPayment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $paymentsToNotify->push($newPayment);
                }
            }
            
            // Remove payments no longer included
            $paymentsToRemove = $allPayments->reject(function ($p) use ($activeStudentIds) {
                return in_array($p->student_id, $activeStudentIds, true);
            });
            
            foreach ($paymentsToRemove as $removePayment) {
                $invoiceIds = collect();
                foreach ($removePayment->allocations as $allocation) {
                    if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                        $invoiceIds->push($allocation->invoiceItem->invoice_id);
                    }
                    $allocation->delete();
                }
                
                $removePayment->update([
                    'reversed' => true,
                    'reversed_by' => auth()->id(),
                    'reversed_at' => now(),
                    'reversal_reason' => 'Removed from shared allocation update',
                ]);
                $removePayment->delete();
                
                foreach ($invoiceIds->unique() as $invoiceId) {
                    $invoice = \App\Models\Invoice::find($invoiceId);
                    if ($invoice) {
                        \App\Services\InvoiceService::recalc($invoice);
                    }
                }
            }
            
            // Increment version for optimistic locking
            $payment->increment('version');
            
            // Log audit trail
            try {
                \App\Services\FinancialAuditService::logPaymentSharedAllocationEdit(
                    $payment, 
                    $oldAllocations, 
                    $activeAllocations
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to log shared allocation edit audit', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            $siblingCount = count($activeAllocations);
            return [
                'message' => "Shared allocations updated successfully. Payment shared among {$siblingCount} sibling(s). Receipts and statements have been regenerated.",
                'payments_to_notify' => $paymentsToNotify->unique('id')->values(),
            ];
        });
        
        foreach ($result['payments_to_notify'] as $notifyPayment) {
            try {
                $this->sendPaymentNotifications($notifyPayment->fresh());
            } catch (\Exception $e) {
                \Log::warning('Failed to send notification after shared allocation update', [
                    'payment_id' => $notifyPayment->id,
                    'error' => $e->getMessage(),
                ]);
                flash_sms_credit_warning($e);
            }
        }
        
        return redirect()
            ->route('finance.payments.show', $payment)
            ->with('success', $result['message']);
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
    
    public function reverse(Request $request, Payment $payment)
    {
        // Authorization check
        $this->authorize('reverse', $payment);
        
        if ($payment->reversed) {
            return back()->with('error', 'This payment has already been reversed.');
        }
        
        // Validate reversal reason if provided
        $validated = $request->validate([
            'reversal_reason' => 'nullable|string|max:500',
        ]);
        
        // Store old values for audit log
        $oldValues = [
            'reversed' => false,
            'amount' => $payment->amount,
            'allocated_amount' => $payment->allocated_amount,
        ];
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $validated, $oldValues) {
            // Collect invoice IDs from allocations before deleting them
            $invoiceIds = collect();
            
            // Reverse all payment allocations and collect invoice IDs
            foreach ($payment->allocations as $allocation) {
                if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                    $invoiceIds->push($allocation->invoiceItem->invoice_id);
                }
                $allocation->delete();
            }
            
            // Mark payment as reversed and increment version
            // Also update allocated_amount to 0 since all allocations are deleted
            $payment->update([
                'reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => $validated['reversal_reason'] ?? null,
                'allocated_amount' => 0, // Reset allocated amount since all allocations are deleted
            ]);
            $payment->increment('version');
            
            // Update allocation totals to ensure consistency
            $payment->updateAllocationTotals();
            
            // Log audit trail
            try {
                \App\Services\FinancialAuditService::logPaymentReversal($payment, $oldValues);
            } catch (\Exception $e) {
                \Log::warning('Failed to log payment reversal audit', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Update related bank statement transaction(s) if exists
            // First, find transactions linked directly by payment_id (for single payments)
            $directLinkedTransactions = \App\Models\BankStatementTransaction::where('payment_id', $payment->id)->get();
            
            // Also find transactions with the same reference_number (for shared payments)
            $referenceLinkedTransactions = collect();
            if ($payment->transaction_code) {
                $possibleRefs = collect([$payment->transaction_code]);
                if (preg_match('/^(.*)-\d+$/', $payment->transaction_code, $matches)) {
                    $possibleRefs->push($matches[1]);
                }
                $referenceLinkedTransactions = \App\Models\BankStatementTransaction::whereIn('reference_number', $possibleRefs->unique()->values()->all())->get();
            }
            
            // Merge and deduplicate
            $bankTransactions = $directLinkedTransactions->merge($referenceLinkedTransactions)->unique('id');
            
            foreach ($bankTransactions as $bankTransaction) {
                // Check if ALL payments for this transaction are now reversed
                $transactionReference = $bankTransaction->reference_number ?? $payment->transaction_code;
                $allRelatedPayments = 0;
                
                if ($transactionReference) {
                    $allRelatedPayments = \App\Models\Payment::where('reversed', false)
                        ->where(function ($q) use ($transactionReference) {
                            $q->where('transaction_code', $transactionReference)
                              ->orWhere('transaction_code', 'LIKE', $transactionReference . '-%');
                        })
                        ->count();
                }
                
                // Also check if the transaction's payment_id points to a reversed payment
                $transactionPaymentReversed = false;
                if ($bankTransaction->payment_id) {
                    $transactionPayment = \App\Models\Payment::find($bankTransaction->payment_id);
                    if ($transactionPayment && $transactionPayment->reversed) {
                        $transactionPaymentReversed = true;
                    }
                }
                
                // Move transaction to unallocated uncollected if:
                // 1. ALL payments for this transaction are reversed, OR
                // 2. The transaction's payment_id points to a reversed payment
                if ($allRelatedPayments == 0 || $transactionPaymentReversed) {
                    // Move transaction to "unallocated uncollected" status:
                    // - Keep status as 'confirmed' (don't reset to draft)
                    // - Set payment_created = false (uncollected)
                    // - Set payment_id = null (unallocated)
                    // - Increment version for optimistic locking
                    $bankTransaction->update([
                        'payment_created' => false,
                        'payment_id' => null,
                        // Keep status as 'confirmed' - this is the "unallocated uncollected" state
                    ]);
                    $bankTransaction->increment('version');
                    
                    \Log::info('Bank statement transaction moved to unallocated uncollected status after payment reversal', [
                        'bank_transaction_id' => $bankTransaction->id,
                        'payment_id' => $payment->id,
                        'status' => $bankTransaction->status,
                        'payment_created' => false,
                        'remaining_non_reversed_payments' => $allRelatedPayments,
                        'transaction_payment_reversed' => $transactionPaymentReversed,
                    ]);
                } else {
                    \Log::info('Payment reversed but transaction remains active due to remaining payments', [
                        'bank_transaction_id' => $bankTransaction->id,
                        'payment_id' => $payment->id,
                        'remaining_non_reversed_payments' => $allRelatedPayments,
                    ]);
                }
            }
            
            // Recalculate affected invoices (unique invoice IDs)
            $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds->unique())->get();
            
            $invoiceCount = $invoices->count();
            $affectedStudents = $invoices->pluck('student_id')->unique()->count();
            
            foreach ($invoices as $invoice) {
                \App\Services\InvoiceService::recalc($invoice);
            }
            
            $message = 'Payment reversed successfully. ';
            if ($invoiceCount > 0) {
                $message .= "{$invoiceCount} invoice(s) recalculated across {$affectedStudents} student(s). ";
            }
            $message .= 'All allocations have been removed.';
            
            return back()->with('success', $message);
        });
    }
    
    /**
     * Show payment history/audit trail
     */
    public function history(Payment $payment)
    {
        $this->authorize('view', $payment);
        
        $auditLogs = \App\Models\AuditLog::where('auditable_type', Payment::class)
            ->where('auditable_id', $payment->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('finance.payments.history', compact('payment', 'auditLogs'));
    }
    
    public function transfer(Request $request, Payment $payment)
    {
        // Authorization check
        $this->authorize('transfer', $payment);
        
        if ($payment->reversed) {
            return back()->with('error', 'Cannot transfer a reversed payment.');
        }
        
        // Allow transfer of allocated amounts - validate against total payment amount
        $maxTransferAmount = $payment->amount;
        
        $request->validate([
            'transfer_type' => 'required|in:transfer,share',
            'target_student_id' => 'required_if:transfer_type,transfer|exists:students,id',
            'transfer_amount' => 'required_if:transfer_type,transfer|numeric|min:0.01|max:' . $maxTransferAmount,
            'shared_students' => 'required_if:transfer_type,share|array',
            'shared_students.*' => 'nullable|exists:students,id',
            'shared_amounts' => 'required_if:transfer_type,share|array',
            'shared_amounts.*' => 'nullable|numeric|min:0',
            'transfer_reason' => 'nullable|string|max:500',
        ]);
        
        return DB::transaction(function () use ($request, $payment) {
            $originalStudent = $payment->student;
            $affectedInvoices = collect();
            
            if ($request->transfer_type === 'transfer') {
                // Single student transfer
                $targetStudent = Student::withAlumni()->findOrFail($request->target_student_id);
                $transferAmount = (float)$request->transfer_amount;
                
                // For alumni or archived students, enforce balance limit
                $isAlumniOrArchived = $targetStudent->is_alumni || $targetStudent->archive;
                if ($isAlumniOrArchived) {
                    $targetBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($targetStudent);
                    if ($transferAmount > $targetBalance) {
                        return back()->with('error', "Cannot transfer more than outstanding balance for " . ($targetStudent->is_alumni ? 'alumni' : 'archived') . " student. Maximum: Ksh " . number_format($targetBalance, 2));
                    }
                }
                
                // Deallocate from original payment if needed (FIFO - oldest allocations first)
                $deallocatedInvoices = $this->deallocatePaymentAmount($payment, $transferAmount);
                $affectedInvoices = $affectedInvoices->merge($deallocatedInvoices);
                
                // Create new payment for target student
                $newPayment = Payment::create([
                    'student_id' => $targetStudent->id,
                    'amount' => $transferAmount,
                    'payment_method_id' => $payment->payment_method_id,
                    'payment_date' => $payment->payment_date,
                    'transaction_code' => $payment->transaction_code . '-T' . $targetStudent->id,
                    'payer_name' => $payment->payer_name,
                    'payer_type' => $payment->payer_type,
                    'narration' => ($request->transfer_reason ?? 'Transferred from payment ' . $payment->transaction_code),
                ]);
                
                // Reduce original payment amount
                $payment->decrement('amount', $transferAmount);
                
                // Update allocation totals for original payment
                $payment->updateAllocationTotals();
                
                // Recalculate affected invoices for original student
                foreach ($affectedInvoices as $invoice) {
                    \App\Services\InvoiceService::recalc($invoice);
                }
                
                // Auto-allocate to target student's payment plan installments then invoices
                if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                    $this->allocationService->autoAllocateWithInstallments($newPayment, $targetStudent->id);
                } else {
                    $this->allocationService->autoAllocate($newPayment, $targetStudent->id);
                }
                
                // Get invoices for target student that were affected
                $targetInvoices = \App\Models\Invoice::where('student_id', $targetStudent->id)->get();
                foreach ($targetInvoices as $invoice) {
                    \App\Services\InvoiceService::recalc($invoice);
                }
                
                // Log audit trail
                try {
                    \App\Services\FinancialAuditService::logPaymentTransfer($payment, $newPayment, $transferAmount);
                } catch (\Exception $e) {
                    \Log::warning('Failed to log payment transfer audit', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                // Update receipt for original payment
                try {
                    $this->receiptService->generateReceipt($payment->fresh(), ['save' => true]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to update receipt for original payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Send communications
                $this->sendFeeUpdateNotification($originalStudent, $transferAmount, $payment);
                $this->sendPaymentNotifications($newPayment);
                
                return redirect()
                    ->route('finance.payments.index')
                    ->with('success', "Payment of Ksh " . number_format($transferAmount, 2) . " transferred to {$targetStudent->full_name}.")
                    ->with('receipt_ids', [$newPayment->id])
                    ->with('payment_id', $newPayment->id);
            } else {
                // Share among multiple students
                $sharedStudents = $request->shared_students ?? [];
                $sharedAmounts = $request->shared_amounts ?? [];
                
                $rawAllocations = collect($sharedStudents)->map(function ($studentId, $index) use ($sharedAmounts) {
                    $amount = (float) ($sharedAmounts[$index] ?? 0);
                    return [
                        'student_id' => (int) $studentId,
                        'amount' => $amount,
                    ];
                })->filter(function ($allocation) {
                    return $allocation['student_id'] > 0 && $allocation['amount'] > 0;
                });
                
                if ($rawAllocations->isEmpty()) {
                    return back()->with('error', 'At least one student must have an amount greater than 0.');
                }
                
                // Combine duplicates by student id
                $sharedAllocations = $rawAllocations
                    ->groupBy('student_id')
                    ->map(fn($rows) => (float) $rows->sum('amount'))
                    ->map(fn($amount, $studentId) => ['student_id' => (int) $studentId, 'amount' => (float) $amount])
                    ->values()
                    ->all();
                
                $totalShared = (float) collect($sharedAllocations)->sum('amount');
                
                // Validate total equals exactly payment amount (with small tolerance for rounding)
                $tolerance = 0.01;
                if (abs($totalShared - $payment->amount) > $tolerance) {
                    return back()->with('error', 'Total shared amounts must equal exactly the payment amount of Ksh ' . number_format($payment->amount, 2) . '. Current total: Ksh ' . number_format($totalShared, 2));
                }
                
                // Sync bank statement shared allocations if applicable
                $bankTransaction = \App\Models\BankStatementTransaction::where('reference_number', $payment->transaction_code)
                    ->first();
                if ($bankTransaction) {
                    $bankTransaction->update([
                        'is_shared' => true,
                        'shared_allocations' => $sharedAllocations,
                        'match_status' => 'manual',
                        'match_notes' => trim(($bankTransaction->match_notes ?? '') . "\nShared via payment transfer."),
                    ]);
                }
                
                // Deallocate all from original payment since we're sharing the entire amount
                $deallocatedInvoices = $this->deallocatePaymentAmount($payment, $payment->amount);
                $affectedInvoices = $affectedInvoices->merge($deallocatedInvoices);
                
                $newPayments = [];
                $originalPaymentAmount = $payment->amount;
                $sharedReference = $payment->transaction_code;
                $sharedReceiptNumber = $payment->shared_receipt_number ?: $this->generateSharedReceiptNumber();
                
                foreach ($sharedAllocations as $allocation) {
                    $studentId = (int) ($allocation['student_id'] ?? 0);
                    $amount = (float) ($allocation['amount'] ?? 0);
                    $student = Student::withAlumni()->findOrFail($studentId);
                    
                    if ($amount <= 0) {
                        continue; // Skip if no amount allocated
                    }
                    
                    // For alumni or archived students, enforce balance limit
                    $isAlumniOrArchived = $student->is_alumni || $student->archive;
                    if ($isAlumniOrArchived) {
                        $studentBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
                        if ($amount > $studentBalance) {
                            return back()->with('error', "Cannot share payment to " . ($student->is_alumni ? 'alumni' : 'archived') . " student {$student->full_name}. Amount (Ksh " . number_format($amount, 2) . ") exceeds balance (Ksh " . number_format($studentBalance, 2) . ").");
                        }
                    }
                    
                    // Determine if this is the original student or a new recipient
                    $isOriginalStudent = ($student->id == $originalStudent->id);
                    
                    // Build narration
                    if ($isOriginalStudent) {
                        $narration = $request->transfer_reason ?? "Payment shared with other students (Original amount: Ksh " . number_format($originalPaymentAmount, 2) . ", Retained: Ksh " . number_format($amount, 2) . ")";
                    } else {
                        $narration = $request->transfer_reason ?? "Shared from " . $originalStudent->full_name . " (" . $originalStudent->admission_number . ") - Original payment " . $sharedReference;
                    }
                    
                    if ($isOriginalStudent) {
                        // Update the original payment with the new amount
                        $payment->amount = $amount;
                        $payment->narration = $narration;
                        if (!$payment->shared_receipt_number) {
                            $payment->shared_receipt_number = $sharedReceiptNumber;
                        }
                        $payment->save();
                        
                        // Auto-allocate the reduced amount (installments first, then invoice items)
                        if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                            $this->allocationService->autoAllocateWithInstallments($payment, $student->id);
                        } else {
                            $this->allocationService->autoAllocate($payment, $student->id);
                        }
                        
                        // Update receipt for original payment with new amount
                        try {
                            $this->receiptService->generateReceipt($payment->fresh(), ['save' => true]);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to update receipt for original payment', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        // Note: Notification will be sent after transaction commits
                    } else {
                        // Create new payment for shared student
                        \Log::info('Creating shared payment for student', [
                            'student_id' => $student->id,
                            'student_name' => $student->full_name,
                            'amount' => $amount,
                            'original_payment_id' => $payment->id
                        ]);
                        
                        $existingSharedPayment = Payment::where('transaction_code', $sharedReference)
                            ->where('student_id', $student->id)
                            ->where('id', '!=', $payment->id)
                            ->first();
                        
                        if ($existingSharedPayment) {
                            // Update existing shared payment to avoid duplicate constraint violation
                            \App\Models\PaymentAllocation::where('payment_id', $existingSharedPayment->id)->delete();
                            
                            $existingSharedPayment->fill([
                                'amount' => $amount,
                                'payment_method_id' => $payment->payment_method_id,
                                'payment_date' => $payment->payment_date,
                                'payer_name' => $payment->payer_name,
                                'payer_type' => $payment->payer_type,
                                'narration' => $narration,
                            ]);
                            
                            if (!$existingSharedPayment->shared_receipt_number) {
                                $existingSharedPayment->shared_receipt_number = $sharedReceiptNumber;
                            }
                            
                            $existingSharedPayment->save();
                            $newPayment = $existingSharedPayment;
                        } else {
                            $receiptNumber = $this->ensureUniqueReceiptNumber($sharedReceiptNumber . '-S' . $student->id, $student->id);
                            
                            $newPayment = Payment::create([
                                'student_id' => $student->id,
                                'amount' => $amount,
                                'payment_method_id' => $payment->payment_method_id,
                                'payment_date' => $payment->payment_date,
                                'transaction_code' => $sharedReference, // Same transaction code for all siblings
                                'receipt_number' => $receiptNumber, // Unique receipt number for each sibling
                                'shared_receipt_number' => $sharedReceiptNumber,
                                'payer_name' => $payment->payer_name,
                                'payer_type' => $payment->payer_type,
                                'narration' => $narration,
                            ]);
                        }
                        
                        \Log::info('Shared payment created successfully', [
                            'new_payment_id' => $newPayment->id,
                            'student_id' => $student->id,
                            'amount' => $amount
                        ]);
                        
                        $newPayments[] = $newPayment;
                        
                        // Auto-allocate to new student's payment plan installments then invoices
                        try {
                            if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                                $this->allocationService->autoAllocateWithInstallments($newPayment, $student->id);
                            } else {
                                $this->allocationService->autoAllocate($newPayment, $student->id);
                            }
                        } catch (\Exception $e) {
                            \Log::error('Failed to auto-allocate shared payment', [
                                'payment_id' => $newPayment->id,
                                'student_id' => $student->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                        
                        // Generate receipt for new payment
                        try {
                            $this->receiptService->generateReceipt($newPayment, ['save' => true]);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to generate receipt for shared payment', [
                                'payment_id' => $newPayment->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Recalculate invoices for this student
                    try {
                        $studentInvoices = \App\Models\Invoice::where('student_id', $student->id)->get();
                        foreach ($studentInvoices as $invoice) {
                            \App\Services\InvoiceService::recalc($invoice);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to recalculate invoices for shared payment', [
                            'student_id' => $student->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Recalculate affected invoices for original student
                try {
                    foreach ($affectedInvoices as $invoice) {
                        \App\Services\InvoiceService::recalc($invoice);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to recalculate affected invoices', [
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Store data for notifications (to be sent after transaction commits)
                $notificationData = [
                    'original_student' => $originalStudent,
                    'original_payment' => $payment->fresh(),
                    'new_payments' => $newPayments,
                    'is_original_retained' => $payment->amount > 0
                ];
                
                $recipientCount = count(array_filter($sharedAmounts, function($amt) { return $amt > 0; }));
                
                // Return success with notification data
                $payload = [
                    'success' => true,
                    'message' => 'Payment of Ksh ' . number_format($originalPaymentAmount, 2) . ' successfully shared among ' . $recipientCount . ' student(s).',
                    'notification_data' => $notificationData
                ];
                if ($request->expectsJson()) {
                    return $payload;
                }
                return redirect()
                    ->route('finance.payments.index')
                    ->with('success', $payload['message'])
                    ->with('receipt_ids', array_values(array_unique(array_merge([$payment->id], collect($newPayments)->pluck('id')->all()))))
                    ->with('payment_id', $payment->id);
            }
        });

        // If transaction was successful, send notifications
        if (is_array($result) && isset($result['success']) && $result['success']) {
            $notificationData = $result['notification_data'];
            
            // Send notifications to all affected students (outside transaction to prevent rollback)
            try {
                // Send to original student if they retained some amount
                if ($notificationData['is_original_retained']) {
                    $this->sendPaymentNotifications($notificationData['original_payment']);
                }
                
                // Send to new students
                foreach ($notificationData['new_payments'] as $newPayment) {
                    $this->sendPaymentNotifications($newPayment->fresh());
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send notifications after payment sharing', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                flash_sms_credit_warning($e);
                // Don't fail the request - payments were already created successfully
            }
            
            if ($request->expectsJson()) {
                return response()->json($result);
            }
            
            return redirect()
                ->route('finance.payments.show', $payment)
                ->with('success', $result['message']);
        }

        if ($request->expectsJson() && is_array($result)) {
            return response()->json($result);
        }
        
        return $result; // Return the redirect response from the transaction
    }
    
    /**
     * Deallocate a specific amount from a payment (FIFO - oldest allocations first)
     * Returns collection of affected invoices
     */
    protected function deallocatePaymentAmount(Payment $payment, float $amountToDeallocate): \Illuminate\Support\Collection
    {
        $remaining = $amountToDeallocate;
        $affectedInvoices = collect();
        
        // Get allocations ordered by date (oldest first)
        $allocations = \App\Models\PaymentAllocation::where('payment_id', $payment->id)
            ->with('invoiceItem.invoice')
            ->orderBy('allocated_at', 'asc')
            ->get();
        
        foreach ($allocations as $allocation) {
            if ($remaining <= 0) {
                break;
            }
            
            $allocationAmount = (float)$allocation->amount;
            $invoice = $allocation->invoiceItem?->invoice;
            
            if ($allocationAmount <= $remaining) {
                // Delete entire allocation
                $remaining -= $allocationAmount;
                $allocation->delete();
                
                if ($invoice && !$affectedInvoices->contains('id', $invoice->id)) {
                    $affectedInvoices->push($invoice);
                }
            } else {
                // Partially deallocate
                $newAmount = $allocationAmount - $remaining;
                $allocation->update(['amount' => $newAmount]);
                $remaining = 0;
                
                if ($invoice && !$affectedInvoices->contains('id', $invoice->id)) {
                    $affectedInvoices->push($invoice);
                }
            }
        }
        
        return $affectedInvoices;
    }
    
    /**
     * Send fee update notification to student when payment is transferred/shared
     */
    protected function sendFeeUpdateNotification(Student $student, float $amount, Payment $originalPayment)
    {
        $parent = $student->parent;
        if (!$parent) {
            return;
        }
        
        // Get parent contact info
        $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null;
        $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null;
        
        if (!$parentPhone && !$parentEmail) {
            return;
        }
        
        // Get or create fee update template
        $smsTemplate = \App\Models\CommunicationTemplate::where('code', 'fee_update_sms')
            ->orWhere('code', 'finance_fee_update_sms')
            ->first();
        
        $emailTemplate = \App\Models\CommunicationTemplate::where('code', 'fee_update_email')
            ->orWhere('code', 'finance_fee_update_email')
            ->first();
        
        if (!$smsTemplate) {
            $smsTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                ['code' => 'fee_update_sms'],
                [
                    'title' => 'Fee Update SMS',
                    'type' => 'sms',
                    'subject' => null,
                    'content' => "Dear {{parent_name}}, Payment of Ksh {{amount}} has been transferred from {{student_name}}'s account. Updated balance: Ksh {{balance}}. View statement: {{statement_link}}",
                ]
            );
        }
        
        if (!$emailTemplate) {
            $emailTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                ['code' => 'fee_update_email'],
                [
                    'title' => 'Fee Update Email',
                    'type' => 'email',
                    'subject' => 'Fee Account Update - {{student_name}}',
                    'content' => "<p>Dear {{parent_name}},</p><p>This is to inform you that a payment of <strong>Ksh {{amount}}</strong> has been transferred from <strong>{{student_name}}</strong>'s fee account.</p><p><strong>Transaction Code:</strong> {{transaction_code}}<br><strong>Date:</strong> {{payment_date}}</p><p><strong>Updated Balance:</strong> Ksh {{balance}}</p><p><a href=\"{{statement_link}}\">View Updated Statement</a></p><p>If you have any questions, please contact the finance office.</p>",
                ]
            );
        }
        
        // Prepare template variables
        $balance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        $statementLink = url('/finance/student-statements/' . $student->id);
        
        $variables = [
            'parent_name' => $parent->father_name ?? $parent->mother_name ?? 'Parent',
            'student_name' => $student->full_name,
            'admission_number' => $student->admission_number ?? '',
            'amount' => number_format($amount, 2),
            'balance' => number_format($balance, 2),
            'transaction_code' => $originalPayment->transaction_code,
            'payment_date' => $originalPayment->payment_date->format('d/m/Y'),
            'statement_link' => $statementLink,
        ];
        
        // Replace template variables
        $smsContent = $smsTemplate->content;
        $emailSubject = $emailTemplate->subject;
        $emailContent = $emailTemplate->content;
        
        foreach ($variables as $key => $value) {
            $smsContent = str_replace('{{' . $key . '}}', $value, $smsContent);
            $emailSubject = str_replace('{{' . $key . '}}', $value, $emailSubject);
            $emailContent = str_replace('{{' . $key . '}}', $value, $emailContent);
        }
        
        // Send SMS
        if ($parentPhone) {
            try {
                $this->commService->sendSMS(
                    'student',
                    $student->id,
                    $parentPhone,
                    $smsContent,
                    'Fee Account Update'
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to send fee update SMS', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
                flash_sms_credit_warning($e);
            }
        }
        
        // Send Email
        if ($parentEmail) {
            try {
                $this->commService->sendEmail(
                    'student',
                    $student->id,
                    $parentEmail,
                    $emailSubject,
                    $emailContent
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to send fee update email', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    public function printReceipt(Payment $payment)
    {
        try {
            $payment->load([
                'student.classroom', 
                'student.family.updateLink',
                'invoice', 
                'paymentMethod', 
                'allocations.invoiceItem.votehead',
                'allocations.invoiceItem.invoice'
            ]);
            
            $student = $payment->student;
            
            // Get ALL unpaid invoice items for the student
            $allUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->where('status', 'active')
            ->with(['invoice', 'votehead', 'allocations'])
            ->get()
            ->filter(function($item) {
                return $item->getBalance() > 0;
            });
            
            // Get payment allocations for this specific payment
            $paymentAllocations = $payment->allocations;
            
            // Build comprehensive receipt items
            $receiptItems = collect();
            
            // First, add items that received payment
            foreach ($paymentAllocations as $allocation) {
                $item = $allocation->invoiceItem;
                $itemAmount = $item->amount ?? 0;
                $discountAmount = $item->discount_amount ?? 0;
                $allocatedAmount = $allocation->amount;
                $balanceBefore = $item->getBalance() + $allocatedAmount;
                $balanceAfter = $item->getBalance();
                
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
            
            // Then, add all other unpaid items
            $paidItemIds = $paymentAllocations->pluck('invoice_item_id')->toArray();
            foreach ($allUnpaidItems as $item) {
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
            
            // Calculate total outstanding balance (current balance AFTER this payment)
            $invoices = \App\Models\Invoice::where('student_id', $student->id)->get();
            $currentOutstandingBalance = 0;
            foreach ($invoices as $invoice) {
                $invoice->recalculate();
                $currentOutstandingBalance += max(0, $invoice->balance ?? 0);
            }
            
            // Balance before this payment = current balance + payment amount
            $balanceBeforePayment = $currentOutstandingBalance + $payment->amount;
            
            // Get school settings and branding
            $schoolSettings = $this->getSchoolSettings();
            $branding = $this->branding();
            $receiptHeader = \App\Models\Setting::get('receipt_header', '');
            $receiptFooter = \App\Models\Setting::get('receipt_footer', '');
            
            // Prepare data for print view
            $data = [
                'payment' => $payment,
                'school' => $schoolSettings,
                'branding' => $branding,
                'receipt_number' => $payment->shared_receipt_number ?? $payment->receipt_number,
                'date' => $payment->payment_date->format('d M Y'),
                'student' => $student,
                'total_amount' => $payment->amount,
                'total_outstanding_balance' => $balanceBeforePayment, // This is the balance BEFORE payment
                'current_outstanding_balance' => $currentOutstandingBalance, // Balance AFTER payment
                'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
                'transaction_code' => $payment->transaction_code,
                'narration' => $payment->narration,
                'receiptHeader' => $receiptHeader,
                'receiptFooter' => $receiptFooter,
            ];
            
            return view('finance.receipts.print', $data);
        } catch (\Exception $e) {
            \Log::error('Receipt print view failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Receipt print view failed: ' . $e->getMessage());
        }
    }

    public function bulkPrintReceipts(Request $request)
    {
        try {
            $query = Payment::with([
                'student.classroom', 
                'student.stream', 
                'paymentMethod', 
                'invoice',
                'allocations.invoiceItem.votehead',
                'allocations.invoiceItem.invoice'
            ])
            ->whereHas('student', function($q) {
                $q->where('archive', 0)->where('is_alumni', false);
            })
            ->where('reversed', false); // Exclude reversed payments
        
            // Apply filters (same as index)
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            
            if ($request->filled('class_id')) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('classroom_id', $request->class_id);
                });
            }
            
            if ($request->filled('stream_id')) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('stream_id', $request->stream_id);
                });
            }
            
            if ($request->filled('payment_method_id')) {
                $query->where('payment_method_id', $request->payment_method_id);
            }
            
            if ($request->filled('from_date')) {
                $query->whereDate('payment_date', '>=', $request->from_date);
            }
            
            if ($request->filled('to_date')) {
                $query->whereDate('payment_date', '<=', $request->to_date);
            }
            
            // If specific payment IDs are provided, use those
            if ($request->filled('payment_ids')) {
                $paymentIds = is_array($request->payment_ids) 
                    ? $request->payment_ids 
                    : explode(',', $request->payment_ids);
                $query->whereIn('id', $paymentIds);
            }
            
            $payments = $query->orderBy('payment_date')->orderBy('id')->get();
            
            if ($payments->isEmpty()) {
                return back()->with('error', 'No payments found matching the selected criteria.');
            }
            
            // Get school settings and branding (shared across all receipts)
            $schoolSettings = $this->getSchoolSettings();
            $branding = $this->branding();
            $receiptHeader = \App\Models\Setting::get('receipt_header', '');
            $receiptFooter = \App\Models\Setting::get('receipt_footer', '');
            
            // Prepare receipt data for each payment using the same format as ReceiptService
            $receiptsData = [];
            foreach ($payments as $payment) {
                $student = $payment->student;
                
                // Get ALL unpaid invoice items for the student (same as ReceiptService)
                $allUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student) {
                    $q->where('student_id', $student->id);
                })
                ->where('status', 'active')
                ->with(['invoice', 'votehead', 'allocations'])
                ->get()
                ->filter(function($item) {
                    return $item->getBalance() > 0;
                });
                
                // Get payment allocations for this specific payment
                $paymentAllocations = $payment->allocations;
                
                // Build comprehensive receipt items (same as ReceiptService)
                $receiptItems = collect();
                
                // First, add items that received payment from this payment
                foreach ($paymentAllocations as $allocation) {
                    $item = $allocation->invoiceItem;
                    $itemAmount = $item->amount ?? 0;
                    $discountAmount = $item->discount_amount ?? 0;
                    $allocatedAmount = $allocation->amount;
                    $balanceBefore = $item->getBalance() + $allocatedAmount;
                    $balanceAfter = $item->getBalance();
                    
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
                
                // Then, add all other unpaid items
                $paidItemIds = $paymentAllocations->pluck('invoice_item_id')->toArray();
                foreach ($allUnpaidItems as $item) {
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
                
                // Calculate total invoices
                $invoices = \App\Models\Invoice::where('student_id', $student->id)
                    ->with('items')
                    ->get();
                $totalInvoices = $invoices->sum('total');
                
                $receiptsData[] = [
                    'payment' => $payment,
                    'school' => $schoolSettings,
                    'branding' => $branding,
                    'receipt_number' => $payment->shared_receipt_number ?? $payment->receipt_number,
                    'date' => $payment->payment_date->format('d/m/Y'),
                    'student' => $student,
                    'allocations' => $receiptItems,
                    'total_amount' => $payment->amount,
                    'total_balance_before' => $totalBalanceBefore,
                    'total_balance_after' => $totalBalanceAfter,
                    'total_outstanding_balance' => $totalOutstandingBalance,
                    'total_invoices' => $totalInvoices,
                    'payment_method' => $payment->paymentMethod->name ?? $payment->payment_method,
                    'transaction_code' => $payment->transaction_code,
                    'narration' => $payment->narration,
                    'receipt_header' => $receiptHeader,
                    'receipt_footer' => $receiptFooter,
                ];
            }
            
            return view('finance.receipts.bulk-print-pdf', [
                'receipts' => $receiptsData,
                'school' => $schoolSettings,
                'branding' => $branding,
            ]);
        } catch (\Exception $e) {
            \Log::error('Bulk receipt print failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return back()->with('error', 'Bulk receipt print failed: ' . $e->getMessage());
        }
    }

    public function viewReceipt(Payment $payment)
    {
        $sharedReceiptNumber = $payment->shared_receipt_number;
        $sharedPayments = collect();
        if ($sharedReceiptNumber) {
            $sharedPayments = Payment::where('shared_receipt_number', $sharedReceiptNumber)->orderBy('id')->get();
        } else {
            $sharedPayments = Payment::where('receipt_number', $payment->receipt_number)->orderBy('id')->get();
        }

        if ($sharedPayments->count() > 1) {
            $receiptService = app(ReceiptService::class);
            $receipts = $sharedPayments->map(function ($sharedPayment) use ($receiptService) {
                return $receiptService->buildReceiptData($sharedPayment);
            })->values()->all();

            $first = $receipts[0] ?? [];
            return view('finance.receipts.bulk-print', [
                'receipts' => $receipts,
                'school' => $first['school'] ?? $this->getSchoolSettings(),
                'branding' => $first['branding'] ?? $this->branding(),
                'receiptHeader' => $first['receipt_header'] ?? \App\Models\Setting::get('receipt_header', ''),
                'receiptFooter' => $first['receipt_footer'] ?? \App\Models\Setting::get('receipt_footer', ''),
            ]);
        }

        $payment->load([
            'student.classroom', 
            'invoice', 
            'paymentMethod', 
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);
        
        $student = $payment->student;
        
        // Get ALL unpaid invoice items for the student
        $allUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->where('status', 'active')
        ->with(['invoice', 'votehead', 'allocations'])
        ->get()
        ->filter(function($item) {
            return $item->getBalance() > 0;
        });
        
        // Get payment allocations for this specific payment
        $paymentAllocations = $payment->allocations;
        
        // Build comprehensive receipt items
        $receiptItems = collect();
        
        // First, add items that received payment
        foreach ($paymentAllocations as $allocation) {
            $item = $allocation->invoiceItem;
            $itemAmount = $item->amount ?? 0;
            $discountAmount = $item->discount_amount ?? 0;
            $allocatedAmount = $allocation->amount;
            $balanceBefore = $item->getBalance() + $allocatedAmount;
            $balanceAfter = $item->getBalance();
            
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
        
        // Then, add all other unpaid items
        $paidItemIds = $paymentAllocations->pluck('invoice_item_id')->toArray();
        foreach ($allUnpaidItems as $item) {
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
        
        // Calculate total outstanding balance and total invoices
        $invoices = \App\Models\Invoice::where('student_id', $student->id)->get();
        $totalOutstandingBalance = 0;
        $totalInvoices = 0;
        foreach ($invoices as $invoice) {
            $invoice->recalculate();
            $totalOutstandingBalance += max(0, $invoice->balance ?? 0);
            $totalInvoices += $invoice->total ?? 0;
        }
        
        // Ensure payment has public_token for Pay Now link
        if (!$payment->public_token) {
            $payment->public_token = Payment::generatePublicToken();
            $payment->save();
        }

        // Get school settings and branding (including logo)
        $schoolSettings = $this->getSchoolSettings();
        $branding = $this->branding();
        
        return view('finance.receipts.view', compact(
            'payment',
            'schoolSettings',
            'branding',
            'totalBalanceBefore',
            'totalBalanceAfter',
            'totalOutstandingBalance',
            'totalInvoices'
        ))->with([
            'allocations' => $receiptItems,
            'total_outstanding_balance' => $totalOutstandingBalance,
            'total_invoices' => $totalInvoices
        ]);
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
            'student.family.updateLink', // Load family and updateLink for profile update button
            'invoice', 
            'paymentMethod', 
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);
        
        $student = $payment->student;
        
        // Ensure student has a family (create if doesn't exist)
        if (!$student->family_id) {
            // Create a family for the student if they don't have one
            $family = \App\Models\Family::create([
                'guardian_name' => $student->first_name . ' ' . $student->last_name,
            ]);
            $student->update(['family_id' => $family->id]);
            // Refresh student to get the new family relationship
            $student->refresh();
            $payment->refresh();
        }
        
        // Reload student with family and updateLink
        $student->load('family.updateLink');
        
        // Ensure family has an update link (create if doesn't exist)
        if ($student->family) {
            if (!$student->family->updateLink) {
                \App\Models\FamilyUpdateLink::create([
                    'family_id' => $student->family->id,
                    'is_active' => true,
                ]);
                // Reload the relationship to ensure updateLink is available
                $student->family->refresh();
                $student->family->load('updateLink');
            }
        }

        $sharedReceiptNumber = $payment->shared_receipt_number;
        $sharedPayments = collect();
        if ($sharedReceiptNumber) {
            $sharedPayments = Payment::where('shared_receipt_number', $sharedReceiptNumber)->orderBy('id')->get();
        } else {
            $sharedPayments = Payment::where('receipt_number', $payment->receipt_number)->orderBy('id')->get();
        }

        if ($sharedPayments->count() > 1) {
            $receiptService = app(ReceiptService::class);
            $receipts = $sharedPayments->map(function ($sharedPayment) use ($receiptService) {
                return $receiptService->buildReceiptData($sharedPayment);
            })->values()->all();

            $first = $receipts[0] ?? [];
            return view('finance.receipts.bulk-print', [
                'receipts' => $receipts,
                'school' => $first['school'] ?? $this->getSchoolSettings(),
                'branding' => $first['branding'] ?? $this->branding(),
                'receiptHeader' => $first['receipt_header'] ?? \App\Models\Setting::get('receipt_header', ''),
                'receiptFooter' => $first['receipt_footer'] ?? \App\Models\Setting::get('receipt_footer', ''),
            ]);
        }
        
        // Get ALL unpaid invoice items for the student
        $allUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->where('status', 'active')
        ->with(['invoice', 'votehead', 'allocations'])
        ->get()
        ->filter(function($item) {
            return $item->getBalance() > 0;
        });
        
        // Get payment allocations for this specific payment
        $paymentAllocations = $payment->allocations;
        
        // Build comprehensive receipt items
        $receiptItems = collect();
        
        // First, add items that received payment
        foreach ($paymentAllocations as $allocation) {
            $item = $allocation->invoiceItem;
            $itemAmount = $item->amount ?? 0;
            $discountAmount = $item->discount_amount ?? 0;
            $allocatedAmount = $allocation->amount;
            $balanceBefore = $item->getBalance() + $allocatedAmount;
            $balanceAfter = $item->getBalance();
            
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
        
        // Then, add all other unpaid items
        $paidItemIds = $paymentAllocations->pluck('invoice_item_id')->toArray();
        foreach ($allUnpaidItems as $item) {
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
        
        // Calculate total outstanding balance and total invoices
        $invoices = \App\Models\Invoice::where('student_id', $student->id)->get();
        $totalOutstandingBalance = 0;
        $totalInvoices = 0;
        foreach ($invoices as $invoice) {
            $invoice->recalculate();
            $totalOutstandingBalance += max(0, $invoice->balance ?? 0);
            $totalInvoices += $invoice->total ?? 0;
        }
        
        // Get school settings and branding for receipt
        $schoolSettings = $this->getSchoolSettings();
        $branding = $this->branding();
        
        return view('finance.receipts.public', compact(
            'payment', 
            'schoolSettings',
            'branding',
            'totalBalanceBefore',
            'totalBalanceAfter',
            'totalOutstandingBalance',
            'totalInvoices'
        ))->with([
            'allocations' => $receiptItems,
            'total_outstanding_balance' => $totalOutstandingBalance,
            'total_invoices' => $totalInvoices
        ]);
    }

    /**
     * Create a quick payment link from receipt (Pay Now) and redirect to payment page.
     * Public route: used from receipt view when fee balance exists.
     */
    public function createPayNowFromReceiptToken(string $token)
    {
        $payment = Payment::where('public_token', $token)->whereRaw('LENGTH(public_token) = 10')->firstOrFail();
        $payment->load('student');
        $student = $payment->student;
        if (!$student) {
            return redirect()->to('/receipt/' . $token)->with('error', 'Student not found.');
        }
        $feeBalance = (float) Invoice::where('student_id', $student->id)
            ->get()
            ->sum(fn ($inv) => (float) ($inv->balance ?? 0));
        if ($feeBalance < 1) {
            return redirect()->to('/receipt/' . $token)->with('info', 'No fee balance to pay.');
        }
        $link = \App\Models\PaymentLink::create([
            'student_id' => $student->id,
            'invoice_id' => null,
            'family_id' => $student->family_id,
            'amount' => $feeBalance,
            'currency' => 'KES',
            'description' => 'Pay remaining fee balance - ' . $student->full_name,
            'account_reference' => $student->admission_number ?? ('STU-' . $student->id),
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'max_uses' => 999,
            'created_by' => $payment->created_by ?? null,
            'metadata' => ['source' => 'receipt_pay_now'],
        ]);
        return redirect()->route('payment.link.show', $link->hashed_id);
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
     * Get branding information (logo, name, address, etc.)
     */
    private function branding(): array
    {
        $kv = \Illuminate\Support\Facades\DB::table('settings')->pluck('value','key')->map(fn($v) => trim((string)$v));

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
    
    /**
     * Auto-match payment to bank statement transaction if transaction code matches
     * This prevents double collection of fees
     */
    protected function autoMatchToBankStatement(Payment $payment)
    {
        if (!$payment->transaction_code) {
            return;
        }
        
        // Find unmatched bank statement transaction with matching reference number
        $bankTransaction = \App\Models\BankStatementTransaction::where('reference_number', $payment->transaction_code)
            ->where('match_status', 'unmatched')
            ->where('status', 'draft')
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->whereNull('student_id')
            ->first();
        
        if ($bankTransaction && $bankTransaction->amount == $payment->amount) {
            // Match the transaction to the payment's student
            $bankTransaction->update([
                'student_id' => $payment->student_id,
                'family_id' => $payment->family_id,
                'match_status' => 'matched',
                'match_confidence' => 1.0,
                'matched_phone_number' => $payment->transaction_code,
                'match_notes' => 'Auto-matched to manually created payment #' . ($payment->receipt_number ?? $payment->transaction_code),
            ]);
            
            // Link the payment to the bank transaction
            $bankTransaction->update([
                'payment_id' => $payment->id,
                'payment_created' => true,
            ]);
            
            Log::info('Auto-matched bank statement transaction to payment', [
                'payment_id' => $payment->id,
                'bank_transaction_id' => $bankTransaction->id,
                'transaction_code' => $payment->transaction_code,
            ]);
        }
    }

    /**
     * View failed payment communications
     */
    public function failedCommunications(Request $request)
    {
        // Check for failed communications - include both explicit 'failed' status and error codes
        $query = CommunicationLog::with(['payment.student.parent'])
            ->whereNotNull('payment_id')
            ->where(function($q) {
                $q->where('status', 'failed')
                  ->orWhereNotNull('error_code')
                  ->orWhere(function($subQ) {
                      // Also check for provider_status indicating failure
                      $subQ->where('provider_status', '!=', 'success')
                           ->where('provider_status', '!=', 'sent')
                           ->where('provider_status', '!=', 'delivered')
                           ->whereNotNull('provider_status');
                  });
            })
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by error code
        if ($request->filled('error_code')) {
            $query->where('error_code', $request->error_code);
        }

        $failedCommunications = $query->paginate(20);

        // Get unique error codes for filter dropdown
        $errorCodes = CommunicationLog::whereNotNull('payment_id')
            ->where('status', 'failed')
            ->whereNotNull('error_code')
            ->distinct()
            ->pluck('error_code')
            ->filter()
            ->sort()
            ->values();

        return view('finance.payments.failed-communications', compact('failedCommunications', 'errorCodes'));
    }

    /**
     * Bulk allocate all unallocated payments that have outstanding invoices
     */
    public function bulkAllocateUnallocated(Request $request)
    {
        $allocated = 0;
        $failed = 0;
        $errors = [];
        $skipped = 0;
        
        // Get all unallocated payments
        $unallocatedPayments = Payment::where('reversed', false)
            ->where(function($q) {
                $q->where('unallocated_amount', '>', 0)
                  ->orWhereRaw('amount > allocated_amount');
            })
            // Exclude swimming payments - they are managed separately and allocated to wallets
            ->where('receipt_number', 'not like', 'SWIM-%')
            ->with('student')
            ->get();
        
        foreach ($unallocatedPayments as $payment) {
            try {
                // Only allocate if student exists
                if (!$payment->student_id || !$payment->student) {
                    $errors[] = "Payment #{$payment->receipt_number}: No student associated";
                    $failed++;
                    continue;
                }
                
                // Check if student has any unpaid invoice items
                $hasUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($payment) {
                    $q->where('student_id', $payment->student_id)
                      ->where('status', '!=', 'paid');
                })
                ->where('status', 'active')
                ->get()
                ->filter(function($item) {
                    return $item->getBalance() > 0;
                })
                ->isNotEmpty();
                
                if ($hasUnpaidItems) {
                    if (method_exists($this->allocationService, 'autoAllocateWithInstallments')) {
                        $this->allocationService->autoAllocateWithInstallments($payment);
                    } else {
                        $this->allocationService->autoAllocate($payment);
                    }
                    $allocated++;
                } else {
                    // No unpaid items - this is an overpayment, skip it
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = "Payment #{$payment->receipt_number}: " . $e->getMessage();
                $failed++;
                Log::warning('Bulk allocation failed for payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $message = "Allocated {$allocated} payment(s)";
        if ($skipped > 0) {
            $message .= ". {$skipped} payment(s) skipped (no outstanding invoices).";
        }
        if ($failed > 0) {
            $message .= ". {$failed} payment(s) failed to allocate.";
        }
        
        return redirect()
            ->route('finance.payments.index')
            ->with('success', $message)
            ->with('errors', $errors);
    }

    /**
     * Resend a failed payment communication
     */
    public function resendCommunication(Request $request, CommunicationLog $communicationLog)
    {
        $request->validate([
            'channel' => 'nullable|in:sms,email,both'
        ]);

        if (!$communicationLog->payment_id) {
            return redirect()->back()->with('error', 'This communication is not linked to a payment.');
        }

        $payment = Payment::with(['student.parent', 'paymentMethod'])->findOrFail($communicationLog->payment_id);

        try {
            // Resend payment notifications (sends both SMS and email if available)
            // The sendPaymentNotifications method handles both channels internally
            $this->sendPaymentNotifications($payment);

            return redirect()->back()->with('success', 'Payment communication resent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to resend payment communication', [
                'communication_log_id' => $communicationLog->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            flash_sms_credit_warning($e);
            $errorMsg = $e instanceof \App\Exceptions\InsufficientSmsCreditsException
                ? $e->getPublicMessage()
                : 'Failed to resend communication: ' . $e->getMessage();
            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Resend multiple failed payment communications
     */
    public function resendMultipleCommunications(Request $request)
    {
        $request->validate([
            'communication_ids' => 'required|array',
            'communication_ids.*' => 'exists:communication_logs,id'
        ]);

        $communicationIds = $request->input('communication_ids');
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($communicationIds as $logId) {
            try {
                $communicationLog = CommunicationLog::findOrFail($logId);
                
                if (!$communicationLog->payment_id) {
                    $failureCount++;
                    $errors[] = "Communication #{$logId} is not linked to a payment.";
                    continue;
                }

                $payment = Payment::with(['student.parent', 'paymentMethod'])->findOrFail($communicationLog->payment_id);
                $this->sendPaymentNotifications($payment);
                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
                flash_sms_credit_warning($e);
                $errors[] = $e instanceof \App\Exceptions\InsufficientSmsCreditsException
                    ? "Communication #{$logId}: " . $e->getPublicMessage()
                    : "Communication #{$logId}: " . $e->getMessage();
                Log::error('Failed to resend payment communication in bulk', [
                    'communication_log_id' => $logId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $message = "Resent {$successCount} communication(s) successfully.";
        if ($failureCount > 0) {
            $message .= " {$failureCount} failed.";
        }

        return redirect()->back()->with(
            $failureCount === 0 ? 'success' : 'warning',
            $message
        )->with('errors', $errors);
    }

    /**
     * Bulk send payment notifications to all payments (respecting filters)
     * Skips payments that have already been bulk sent for the selected channels
     */
    /**
     * Show preview of payments to be sent with ability to exclude already-sent ones
     */
    public function bulkSendPreview(Request $request)
    {
        $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,email,whatsapp',
            'student_id' => 'nullable|exists:students,id',
            'class_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $channels = $request->input('channels');
        
        // Build query based on filters (same as index method)
        $query = Payment::with(['student.parent', 'student.classroom', 'paymentMethod'])
            ->where('reversed', false)
            // Exclude swimming payments - they are managed separately
            ->where('receipt_number', 'not like', 'SWIM-%');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('classroom_id', $request->class_id);
            });
        }

        if ($request->filled('stream_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('stream_id', $request->stream_id);
            });
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        // Check which payments have already been sent for the selected channels
        $paymentsPreview = $payments->map(function($payment) use ($channels) {
            $alreadySentChannels = [];
            $bulkSentChannels = $payment->bulk_sent_channels ?? [];
            
            foreach ($channels as $channel) {
                // Check bulk_sent_channels field
                if (in_array($channel, $bulkSentChannels)) {
                    $alreadySentChannels[$channel] = 'bulk_sent';
                    continue;
                }
                
                // Check communication_logs table
                $logExists = \App\Models\CommunicationLog::where('payment_id', $payment->id)
                    ->where('channel', $channel)
                    ->where('status', 'sent')
                    ->exists();
                    
                if ($logExists) {
                    $alreadySentChannels[$channel] = 'communication_log';
                }
            }
            
            return [
                'payment' => $payment,
                'already_sent_channels' => $alreadySentChannels,
                'will_be_skipped' => count($alreadySentChannels) === count($channels),
                'has_parent' => $payment->student && $payment->student->parent,
            ];
        });

        $toSendCount = $paymentsPreview->where('will_be_skipped', false)->where('has_parent', true)->count();
        $toSkipCount = $paymentsPreview->where('will_be_skipped', true)->count();
        $noParentCount = $paymentsPreview->where('has_parent', false)->count();

        return view('finance.payments.bulk-send-preview', [
            'payments' => $paymentsPreview,
            'channels' => $channels,
            'toSendCount' => $toSendCount,
            'toSkipCount' => $toSkipCount,
            'noParentCount' => $noParentCount,
            'filters' => $request->only([
                'student_id', 'class_id', 'stream_id', 'payment_method_id', 'from_date', 'to_date'
            ]),
        ]);
    }

    /**
     * Initiate bulk send using background job with real-time tracking
     */
    public function bulkSend(Request $request)
    {
        $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,email,whatsapp',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
            'student_id' => 'nullable|exists:students,id',
            'class_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $channels = $request->input('channels');
        $paymentIds = $request->input('payment_ids');
        
        // Generate tracking ID
        $trackingId = 'bulk_send_' . uniqid() . '_' . time();
        
        // Dispatch job to background queue
        \App\Jobs\BulkSendPaymentNotifications::dispatch(
            $trackingId,
            $paymentIds,
            $channels,
            auth()->id()
        );
        
        Log::info('Bulk send job dispatched', [
            'tracking_id' => $trackingId,
            'payment_count' => count($paymentIds),
            'channels' => $channels,
            'user_id' => auth()->id()
        ]);
        
        // Redirect to progress tracking page
        return redirect()->route('finance.payments.bulk-send-tracking', [
            'tracking_id' => $trackingId,
            'total' => count($paymentIds),
            'channels' => implode(',', $channels)
        ]);
    }
    
    /**
     * Show bulk send progress tracking page
     */
    public function bulkSendTracking(Request $request)
    {
        $trackingId = $request->query('tracking_id');
        $totalPayments = $request->query('total', 0);
        $channels = explode(',', $request->query('channels', ''));
        
        return view('finance.payments.bulk-send-progress', [
            'trackingId' => $trackingId,
            'totalPayments' => $totalPayments,
            'channels' => $channels
        ]);
    }
    
    /**
     * API endpoint to check bulk send progress
     */
    public function bulkSendProgressCheck(Request $request)
    {
        $trackingId = $request->query('tracking_id');
        
        if (!$trackingId) {
            return response()->json([
                'error' => true,
                'message' => 'Tracking ID is required'
            ], 400);
        }
        
        // Get progress from cache
        $cacheKey = "bulk_send_progress_{$trackingId}";
        $progress = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if (!$progress) {
            return response()->json([
                'error' => true,
                'message' => 'Progress data not found. The job may not have started yet.'
            ], 404);
        }
        
        return response()->json($progress);
    }
    
    /**
     * Old synchronous bulk send method (kept for reference, not used)
     * @deprecated Use bulkSend() with background job instead
     */
    public function bulkSendSynchronous_OLD(Request $request)
    {
        $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,email,whatsapp',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
            'student_id' => 'nullable|exists:students,id',
            'class_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $channels = $request->input('channels');
        $paymentIds = $request->input('payment_ids');
        
        // Build query for selected payment IDs only
        $query = Payment::with(['student.parent', 'paymentMethod'])
            ->whereIn('id', $paymentIds)
            ->where('reversed', false);

        // Process in batches to avoid timeout
        $batchSize = 50; // Process 50 payments at a time
        $totalPayments = $query->count();
        $skippedCount = 0;
        $sentCount = 0;
        $failedCount = 0;
        $errors = [];
        $processed = 0;

        Log::info('Starting bulk send', [
            'total_payments' => $totalPayments,
            'channels' => $channels,
            'batch_size' => $batchSize
        ]);

        // Process payments in batches
        $query->chunk($batchSize, function ($payments) use ($channels, &$skippedCount, &$sentCount, &$failedCount, &$errors, &$processed) {
            foreach ($payments as $payment) {
                $processed++;
                try {
                    // Get already sent channels for this payment
                    $bulkSent = $payment->bulk_sent_channels ?? [];
                    
                    // Filter out channels that have already been sent
                    $channelsToSend = array_filter($channels, function($channel) use ($bulkSent) {
                        return !in_array($channel, $bulkSent);
                    });

                    // Skip if all channels have already been sent
                    if (empty($channelsToSend)) {
                        $skippedCount++;
                        continue;
                    }

                    // Check parent contact info
                    $parent = $payment->student->parent ?? null;
                    if (!$parent) {
                        $skippedCount++;
                        continue;
                    }

                    // Track which channels were successfully sent
                    $sentChannels = [];

                    // Send via each channel that hasn't been sent yet
                    foreach ($channelsToSend as $channel) {
                        try {
                            $hasContact = false;
                            
                            // Check if parent has contact for this channel
                            if ($channel === 'sms') {
                                $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null;
                                $hasContact = !empty($parentPhone);
                            } elseif ($channel === 'email') {
                                $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null;
                                $hasContact = !empty($parentEmail);
                            } elseif ($channel === 'whatsapp') {
                                $whatsappPhone = $parent->father_whatsapp ?? $parent->mother_whatsapp
                                    ?? $parent->father_phone ?? $parent->mother_phone ?? null;
                                $hasContact = !empty($whatsappPhone);
                            }

                            if ($hasContact) {
                                $this->sendPaymentNotificationByChannel($payment, $channel);
                                $sentChannels[] = $channel;
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to send {$channel} for payment {$payment->id}", [
                                'payment_id' => $payment->id,
                                'channel' => $channel,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            $errors[] = "Payment #{$payment->receipt_number} ({$channel}): " . $e->getMessage();
                        }
                    }

                    // Mark channels as bulk sent if any were successfully sent
                    if (!empty($sentChannels)) {
                        $payment->markBulkSent($sentChannels);
                        $sentCount++;
                    } else {
                        // If no channels were sent (no contact info), still count as skipped
                        $skippedCount++;
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Bulk send failed for payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors[] = "Payment #{$payment->receipt_number}: " . $e->getMessage();
                }

                // Small delay every 10 payments to prevent overwhelming the system
                if ($processed % 10 === 0) {
                    usleep(100000); // 0.1 seconds
                }
            }
        });

        Log::info('Bulk send completed', [
            'total_payments' => $totalPayments,
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount
        ]);

        $message = "Bulk send completed: {$sentCount} sent, {$skippedCount} skipped";
        if ($failedCount > 0) {
            $message .= ", {$failedCount} failed";
        }

        // Include detailed errors in message if there are any (up to 5)
        if (!empty($errors)) {
            $displayErrors = array_slice($errors, 0, 5);
            $message .= "\n\nErrors:\n" . implode("\n", $displayErrors);
            if (count($errors) > 5) {
                $message .= "\n... and " . (count($errors) - 5) . " more errors (check logs)";
            }
        }

        return redirect()->route('finance.payments.index', $request->only([
            'student_id', 'class_id', 'stream_id', 'payment_method_id', 'from_date', 'to_date'
        ]))->with(
            $failedCount === 0 ? 'success' : 'warning',
            $message
        );
    }

    /**
     * Send payment notification via a specific channel
     */
    private function sendPaymentNotificationByChannel(Payment $payment, string $channel): void
    {
        $payment->load(['student.parent', 'paymentMethod']);
        $student = $payment->student;
        $parent = $student->parent;
        $profileUpdateLink = $this->getProfileUpdateLinkForStudent($student);

        if (!$parent) {
            return;
        }

        // Get receipt link
        if (!$payment->public_token) {
            $payment->public_token = Payment::generatePublicToken();
            $payment->save();
        }

        try {
            $receiptLink = url('/receipt/' . $payment->public_token);
            $receiptLink = $this->normalizeUrl($receiptLink);
        } catch (\Exception $e) {
            Log::error('Failed to generate receipt link', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            $receiptLink = 'Contact school for receipt details';
        }

        // Get parent name and greeting
        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? null;
        $greeting = $parentName ? "Dear {$parentName}" : "Dear Parent";

        // Calculate outstanding balance
        $outstandingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        $payment->refresh();
        $carriedForward = $payment->unallocated_amount ?? 0;
        $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');

        $displayReceiptNumber = $payment->shared_receipt_number ?? $payment->receipt_number;
        $variables = [
            'parent_name' => $parentName ?? 'Parent',
            'greeting' => $greeting,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'amount' => 'Ksh ' . number_format($payment->amount, 2),
            'receipt_number' => $displayReceiptNumber,
            'transaction_code' => $payment->transaction_code,
            'payment_date' => $payment->payment_date->format('d M Y'),
            'receipt_link' => $receiptLink,
            'finance_portal_link' => $receiptLink,
            'profile_update_link' => $profileUpdateLink,
            'outstanding_amount' => 'Ksh ' . number_format($outstandingBalance, 2),
            'carried_forward' => number_format($carriedForward, 2),
            'school_name' => $schoolName,
        ];

        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };

        // Send via specific channel
        if ($channel === 'sms') {
            $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null;
            if ($parentPhone) {
                $smsTemplate = \App\Models\CommunicationTemplate::where('code', 'payment_receipt_sms')
                    ->orWhere('code', 'finance_payment_received_sms')
                    ->first();
                
                if (!$smsTemplate) {
                    $smsTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                        ['code' => 'payment_receipt_sms'],
                        [
                            'title' => 'Payment Receipt SMS',
                            'type' => 'sms',
                            'subject' => null,
                            'content' => "{{greeting}},\n\nPayment of {{amount}} received for {{student_name}} ({{admission_number}}) on {{payment_date}}.\nReceipt: {{receipt_number}}\nView receipt: {{receipt_link}}\nUpdate profile: {{profile_update_link}}\n\nThank you.\n{{school_name}}",
                        ]
                    );
                }

                $smsVariables = $variables;
                $smsVariables['profile_update_link'] = '';
                $smsMessage = $replacePlaceholders($smsTemplate->content, $smsVariables);
                $smsMessage = preg_replace('/\n?Update profile:.*$/m', '', $smsMessage);
                $smsService = app(\App\Services\SMSService::class);
                $financeSenderId = $smsService->getFinanceSenderId();
                $this->commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, $smsTemplate->subject ?? $smsTemplate->title, $financeSenderId, $payment->id);
            }
        } elseif ($channel === 'email') {
            $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null;
            if ($parentEmail) {
                $emailTemplate = \App\Models\CommunicationTemplate::where('code', 'payment_receipt_email')
                    ->orWhere('code', 'finance_payment_received_email')
                    ->first();
                
                if (!$emailTemplate) {
                    $emailTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                        ['code' => 'payment_receipt_email'],
                        [
                            'title' => 'Payment Receipt Email',
                            'type' => 'email',
                            'subject' => 'Payment Receipt – {{student_name}}',
                            'content' => "<p>{{greeting}},</p><p>We have received a payment of <strong>{{amount}}</strong> for <strong>{{student_name}}</strong> (Admission: {{admission_number}}) on {{payment_date}}.</p><p><strong>Receipt Number:</strong> {{receipt_number}}<br><strong>Transaction Code:</strong> {{transaction_code}}</p><p><a href=\"{{receipt_link}}\" style=\"display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;\">View Receipt</a></p><p><a href=\"{{profile_update_link}}\" style=\"display:inline-block;padding:8px 14px;background:#6c757d;color:#fff;text-decoration:none;border-radius:6px;\">Update Parent Profile</a></p><p>Thank you for your continued support.<br>{{school_name}}</p>",
                        ]
                    );
                }

                $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                if ($profileUpdateLink && strpos($emailContent, $profileUpdateLink) === false) {
                    $emailContent .= "<p><a href=\"{$profileUpdateLink}\" style=\"display:inline-block;padding:8px 14px;background:#6c757d;color:#fff;text-decoration:none;border-radius:6px;\">Update Parent Profile</a></p>";
                }
                $pdfPath = $this->receiptService->generateReceipt($payment, ['save' => true]);
                $this->commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent, $pdfPath);
            }
        } elseif ($channel === 'whatsapp') {
            $whatsappPhone = $parent->father_whatsapp ?? $parent->mother_whatsapp
                ?? $parent->father_phone ?? $parent->mother_phone ?? null;
            
            if ($whatsappPhone) {
                $whatsappTemplate = \App\Models\CommunicationTemplate::where('code', 'payment_receipt_whatsapp')
                    ->orWhere('code', 'finance_payment_received_whatsapp')
                    ->first();
                
                if (!$whatsappTemplate) {
                    $whatsappTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                        ['code' => 'payment_receipt_whatsapp'],
                        [
                            'title' => 'Payment Receipt WhatsApp',
                            'type' => 'whatsapp',
                            'subject' => null,
                            'content' => "{{greeting}},\n\nPayment of {{amount}} received for {{student_name}} ({{admission_number}}) on {{payment_date}}.\nReceipt: {{receipt_number}}\nView receipt: {{receipt_link}}\nUpdate profile: {{profile_update_link}}\n\nThank you.\n{{school_name}}",
                        ]
                    );
                }

                $whatsappMessage = $replacePlaceholders($whatsappTemplate->content, $variables);
                if ($profileUpdateLink && strpos($whatsappMessage, $profileUpdateLink) === false) {
                    $whatsappMessage .= "\nUpdate profile: {$profileUpdateLink}";
                }
                $whatsappService = app(\App\Services\WhatsAppService::class);
                $response = $whatsappService->sendMessage($whatsappPhone, $whatsappMessage);
                
                $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';
                
                \App\Models\CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id'   => $parent->id ?? null,
                    'contact'        => $whatsappPhone,
                    'channel'        => 'whatsapp',
                    'title'          => $whatsappTemplate->subject ?? $whatsappTemplate->title,
                    'message'        => $whatsappMessage,
                    'type'           => 'whatsapp',
                    'status'         => $status,
                    'response'       => $response,
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'payment_id'     => $payment->id,
                    'provider_id'    => data_get($response, 'body.data.id') 
                                        ?? data_get($response, 'body.data.message.id')
                                        ?? data_get($response, 'body.messageId')
                                        ?? data_get($response, 'body.id'),
                    'provider_status'=> data_get($response, 'body.status') ?? data_get($response, 'status'),
                ]);
            }
        }
    }

    protected function generateSharedReceiptNumber(): string
    {
        $maxAttempts = 10;
        $attempt = 0;
        do {
            $receiptNumber = \App\Services\DocumentNumberService::generateReceipt();
            $exists = Payment::where('shared_receipt_number', $receiptNumber)
                ->orWhere('receipt_number', $receiptNumber)
                ->exists();
            $attempt++;
            if ($exists && $attempt < $maxAttempts) {
                usleep(10000);
            }
        } while ($exists && $attempt < $maxAttempts);

        if ($exists) {
            $receiptNumber = $receiptNumber . '-' . time();
        }

        return $receiptNumber;
    }

    protected function ensureUniqueReceiptNumber(string $baseReceiptNumber, int $studentId): string
    {
        $receiptNumber = $baseReceiptNumber;
        $attempt = 0;
        while (Payment::where('receipt_number', $receiptNumber)->exists() && $attempt < 10) {
            $attempt++;
            $receiptNumber = $baseReceiptNumber . '-' . $attempt;
            usleep(10000);
        }

        if (Payment::where('receipt_number', $receiptNumber)->exists()) {
            $receiptNumber = $baseReceiptNumber . '-' . $studentId . '-' . time();
        }

        return $receiptNumber;
    }
}

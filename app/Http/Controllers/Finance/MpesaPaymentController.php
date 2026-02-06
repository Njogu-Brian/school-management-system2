<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentLink;
use App\Models\PaymentTransaction;
use App\Models\MpesaC2BTransaction;
use App\Services\PaymentGateways\MpesaGateway;
use App\Services\PaymentAllocationService;
use App\Services\MpesaSmartMatchingService;
use App\Services\SwimmingWalletService;
use App\Services\SMSService;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MpesaPaymentController extends Controller
{
    protected MpesaGateway $mpesaGateway;
    protected PaymentAllocationService $allocationService;
    protected MpesaSmartMatchingService $smartMatchingService;
    protected SwimmingWalletService $swimmingWalletService;
    protected SMSService $smsService;
    protected EmailService $emailService;
    protected WhatsAppService $whatsappService;

    public function __construct(
        MpesaGateway $mpesaGateway,
        PaymentAllocationService $allocationService,
        MpesaSmartMatchingService $smartMatchingService,
        SwimmingWalletService $swimmingWalletService,
        SMSService $smsService,
        EmailService $emailService,
        WhatsAppService $whatsappService
    ) {
        $this->mpesaGateway = $mpesaGateway;
        $this->allocationService = $allocationService;
        $this->smartMatchingService = $smartMatchingService;
        $this->swimmingWalletService = $swimmingWalletService;
        $this->smsService = $smsService;
        $this->emailService = $emailService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Show admin dashboard for M-PESA payments
     */
    public function dashboard(Request $request)
    {
        // Get statistics
        $stats = [
            'today_transactions' => PaymentTransaction::where('gateway', 'mpesa')
                ->whereDate('created_at', today())
                ->count(),
            'today_amount' => PaymentTransaction::where('gateway', 'mpesa')
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'pending_transactions' => PaymentTransaction::where('gateway', 'mpesa')
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
            'active_payment_links' => PaymentLink::active()->count(),
        ];

        // Validate M-PESA configuration
        $configValidation = $this->mpesaGateway->validateCredentials();

        // Recent transactions
        $recentTransactions = PaymentTransaction::with(['student', 'invoice'])
            ->where('gateway', 'mpesa')
            ->latest()
            ->take(20)
            ->get();

        // Active payment links
        $activeLinks = PaymentLink::with(['student', 'invoice'])
            ->active()
            ->latest()
            ->take(10)
            ->get();

        return view('finance.mpesa.dashboard', compact('stats', 'recentTransactions', 'activeLinks', 'configValidation'));
    }

    /**
     * Show form to initiate admin-prompted STK push
     */
    public function promptPaymentForm(Request $request)
    {
        $student = null;
        $invoice = null;
        $recentTransactions = collect();

        if ($request->filled('student_id')) {
            $student = Student::with(['family', 'invoices' => function($q) {
                $q->where('status', '!=', 'paid')->latest();
            }])->findOrFail($request->student_id);
        }

        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('student')->findOrFail($request->invoice_id);
            $student = $invoice->student;
        }
        
        // Get recent transactions if student is available
        if ($student) {
            $query = PaymentTransaction::with(['invoice'])
                ->where('gateway', 'mpesa')
                ->where('student_id', $student->id);
            
            // If invoice is specified, filter by invoice as well
            if ($invoice) {
                $query->where(function($q) use ($invoice) {
                    $q->where('invoice_id', $invoice->id)
                      ->orWhereNull('invoice_id'); // Include transactions without invoice
                });
            }
            
            $recentTransactions = $query->latest()->take(5)->get();
        }

        return view('finance.mpesa.prompt-payment', compact('student', 'invoice', 'recentTransactions'));
    }

    /**
     * Initiate admin-prompted STK push
     */
    public function promptPayment(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'invoice_id' => 'nullable|exists:invoices,id',
            'notes' => 'nullable|string|max:500',
            'share_with_siblings' => 'nullable|boolean',
            'sibling_allocations' => 'nullable|array',
            'sibling_allocations.*.student_id' => 'required_with:sibling_allocations|exists:students,id',
            'sibling_allocations.*.amount' => 'required_with:sibling_allocations|numeric|min:0',
        ]);

        // Clean and validate phone number
        $phoneNumber = trim($request->phone_number);
        
        if (!MpesaGateway::isValidKenyanPhone($phoneNumber)) {
            return redirect()
                ->route('finance.mpesa.prompt-payment.form', [
                    'student_id' => $request->student_id,
                    'invoice_id' => $request->invoice_id
                ])
                ->with('error', 'Invalid phone number. Please use a valid Kenyan mobile number (e.g., 0712345678 or 254712345678).')
                ->withInput();
        }

        $isShared = $request->boolean('share_with_siblings') && $request->filled('sibling_allocations');
        $sharedAllocations = null;
        $amount = (float) $request->amount;
        $invoiceId = $request->invoice_id;

        if ($isShared && is_array($request->sibling_allocations)) {
            $sharedAllocations = [];
            foreach ($request->sibling_allocations as $a) {
                $am = (float) ($a['amount'] ?? 0);
                if ($am > 0 && isset($a['student_id'])) {
                    $sharedAllocations[] = ['student_id' => (int) $a['student_id'], 'amount' => $am];
                }
            }
            if (empty($sharedAllocations)) {
                return redirect()
                    ->route('finance.mpesa.prompt-payment.form', ['student_id' => $request->student_id])
                    ->with('error', 'When sharing with siblings, at least one sibling must have an amount greater than 0.')
                    ->withInput();
            }
            $amount = array_sum(array_column($sharedAllocations, 'amount'));
            $invoiceId = null;
        } else {
            // When no invoice is selected for school fees, use the first outstanding invoice (pay first one first)
            if (!$request->boolean('is_swimming', false) && empty($invoiceId)) {
                $firstOutstanding = Invoice::where('student_id', $request->student_id)
                    ->where(function ($q) {
                        $q->where('balance', '>', 0)
                            ->orWhereRaw('(COALESCE(total,0) - COALESCE(paid_amount,0)) > 0');
                    })
                    ->orderBy('due_date')
                    ->orderBy('issued_date')
                    ->first();
                if ($firstOutstanding) {
                    $invoiceId = $firstOutstanding->id;
                }
            }
        }

        try {
            $result = $this->mpesaGateway->initiateAdminPromptedPayment(
                studentId: $request->student_id,
                phoneNumber: $phoneNumber,
                amount: $amount,
                invoiceId: $invoiceId,
                adminId: Auth::id(),
                notes: $request->notes,
                isSwimming: $request->boolean('is_swimming', false),
                sharedAllocations: $sharedAllocations
            );

            if ($result['success']) {
                // STK prompt pops up on parent's phone; receipt and communication are sent automatically after successful payment (webhook)
                return redirect()
                    ->route('finance.mpesa.waiting', $result['transaction_id'])
                    ->with('success', 'STK Push sent successfully! Please check your phone for the M-PESA prompt.');
            }

            // Failed - return user-friendly error
            $errorMessage = $this->formatErrorMessage($result['message'] ?? 'Failed to initiate STK Push.');

            return redirect()
                ->route('finance.mpesa.prompt-payment.form', [
                    'student_id' => $request->student_id,
                    'invoice_id' => $request->invoice_id
                ])
                ->with('error', $errorMessage)
                ->withInput();

        } catch (\Exception $e) {
            $errorMessage = $this->formatErrorMessage($e->getMessage());

            return redirect()
                ->route('finance.mpesa.prompt-payment.form', [
                    'student_id' => $request->student_id,
                    'invoice_id' => $request->invoice_id
                ])
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Show payment link creation form
     */
    public function createLinkForm(Request $request)
    {
        $student = null;
        $invoice = null;

        if ($request->filled('student_id')) {
            $student = Student::with(['family', 'invoices' => function($q) {
                $q->where('status', '!=', 'paid')->latest();
            }])->findOrFail($request->student_id);
        }

        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('student')->findOrFail($request->invoice_id);
            $student = $invoice->student;
        }

        return view('finance.mpesa.create-link', compact('student', 'invoice'));
    }

    /**
     * Generate payment link
     */
    public function createLink(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:1',
            'selected_invoices' => 'nullable|string',
            'is_swimming' => 'nullable|boolean',
            'parents' => 'required|array|min:1',
            'parents.*' => 'in:father,mother,primary',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses' => 'nullable|integer|min:1|max:100',
            'send_channels' => 'required|array|min:1',
            'send_channels.*' => 'in:sms,email,whatsapp',
        ]);

        try {
            $student = Student::with('family')->findOrFail($request->student_id);

            // Parse selected invoices
            $invoiceIds = $request->filled('selected_invoices') 
                ? array_filter(explode(',', $request->selected_invoices)) 
                : [];

            $primaryInvoiceId = !empty($invoiceIds) ? (int) $invoiceIds[0] : null;

            $expiresAt = null;
            if (!$request->boolean('never_expire') && $request->filled('expires_in_days') && (int) $request->expires_in_days > 0) {
                $expiresAt = now()->addDays((int) $request->expires_in_days);
            }

            $isSwimming = $request->boolean('is_swimming', false);
            $description = $isSwimming ? 'Swimming Fee Payment' : 'School Fee Payment';
            if (!empty($invoiceIds) && !$isSwimming) {
                $invoices = Invoice::whereIn('id', $invoiceIds)->get();
                $description = 'Payment for ' . implode(', ', $invoices->pluck('invoice_number')->toArray());
            }

            $amount = (float) $request->amount;
            $familyId = $student->family_id;

            // One link per family (like profile-update): reuse same URL for all parents
            $paymentLink = null;
            if ($familyId) {
                $paymentLink = PaymentLink::where('family_id', $familyId)
                    ->whereNull('student_id')
                    ->where('status', 'active')
                    ->first();
            }

            if (!$paymentLink) {
                $accountReference = $familyId ? ('FAM-' . $familyId) : ($isSwimming ? 'SWIM-' . $student->admission_number : $student->admission_number);
                $paymentLink = PaymentLink::create([
                    'student_id' => $familyId ? null : $request->student_id,
                    'invoice_id' => $isSwimming ? null : $primaryInvoiceId,
                    'family_id' => $familyId,
                    'amount' => $amount > 0 ? $amount : 0,
                    'currency' => 'KES',
                    'description' => $description,
                    'account_reference' => $accountReference,
                    'expires_at' => $expiresAt,
                    'max_uses' => (int) ($request->max_uses ?? 99),
                    'created_by' => Auth::id(),
                    'status' => 'active',
                    'metadata' => [
                        'invoice_ids' => $isSwimming ? [] : $invoiceIds,
                        'selected_parents' => $request->parents,
                        'is_swimming' => $isSwimming,
                    ],
                ]);
            }

            $this->sendPaymentLinkToParents(
                $student,
                $paymentLink,
                $request->send_channels,
                $request->parents
            );

            return redirect()
                ->route('finance.mpesa.link.show', $paymentLink->id)
                ->with('success', 'Payment link sent. The same family link was sent to all selected parents.');
        } catch (\Exception $e) {
            Log::error('Payment link creation failed', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create payment link: ' . $e->getMessage());
        }
    }

    /**
     * Show payment link details (admin view)
     */
    public function showLink(PaymentLink $paymentLink)
    {
        $paymentLink->load(['student', 'invoice', 'payment', 'creator']);

        return view('finance.mpesa.link-details', compact('paymentLink'));
    }

    /**
     * List all payment links
     */
    public function listLinks(Request $request)
    {
        $query = PaymentLink::with(['student', 'invoice', 'creator']);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('token', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('admission_number', 'like', "%{$search}%");
                  });
            });
        }

        $links = $query->latest()->paginate(20)->appends($request->all());

        return view('finance.mpesa.links', compact('links'));
    }

    /**
     * Show public payment page (for payment links).
     * Family link (student_id null): load all students in family with fee balances for share/full/partial UI.
     */
    public function showPaymentPage($identifier)
    {
        $paymentLink = PaymentLink::where('hashed_id', $identifier)
            ->orWhere('token', $identifier)
            ->with(['student', 'invoice'])
            ->firstOrFail();

        if (!$paymentLink->isActive()) {
            return view('finance.mpesa.link-expired', compact('paymentLink'));
        }

        $familyStudents = [];
        $isFamilyLink = $paymentLink->student_id === null && $paymentLink->family_id;
        if ($isFamilyLink) {
            $familyStudents = Student::where('family_id', $paymentLink->family_id)
                ->whereNotNull('family_id')
                ->with('classroom')
                ->get()
                ->map(function ($s) {
                    $bal = (float) Invoice::where('student_id', $s->id)
                        ->where(function ($q) {
                            $q->where('balance', '>', 0)->orWhereRaw('(COALESCE(total,0) - COALESCE(paid_amount,0)) > 0');
                        })
                        ->get()
                        ->sum(fn ($inv) => (float) ($inv->balance ?? ($inv->total ?? 0) - ($inv->paid_amount ?? 0)));
                    return [
                        'id' => $s->id,
                        'full_name' => $s->full_name ?? trim($s->first_name . ' ' . $s->last_name),
                        'admission_number' => $s->admission_number,
                        'classroom_name' => $s->classroom?->name,
                        'fee_balance' => round($bal, 2),
                    ];
                })
                ->values()
                ->toArray();
        }

        return view('finance.mpesa.payment-page', compact('paymentLink', 'familyStudents', 'isFamilyLink'));
    }

    /**
     * Process payment from link.
     * Supports family link: share_with_siblings + sibling_allocations, or student_id + amount for single child.
     */
    public function processLinkPayment(Request $request, $identifier)
    {
        $rules = [
            'phone_number' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'share_with_siblings' => 'nullable|boolean',
            'sibling_allocations' => 'nullable|array',
            'sibling_allocations.*.student_id' => 'required_with:sibling_allocations|exists:students,id',
            'sibling_allocations.*.amount' => 'required_with:sibling_allocations|numeric|min:0',
            'student_id' => 'nullable|exists:students,id',
        ];
        $request->validate($rules);

        $paymentLink = PaymentLink::where('hashed_id', $identifier)
            ->orWhere('token', $identifier)
            ->firstOrFail();

        if (!$paymentLink->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment link is no longer active.',
            ], 400);
        }

        if (!MpesaGateway::isValidKenyanPhone($request->phone_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number. Please use a valid Kenyan mobile number.',
            ], 400);
        }

        $isFamilyLink = $paymentLink->student_id === null && $paymentLink->family_id;
        $isShared = $request->boolean('share_with_siblings') && $request->filled('sibling_allocations');

        if ($isFamilyLink && $isShared) {
            $sharedAllocations = [];
            foreach ($request->sibling_allocations ?? [] as $a) {
                $am = (float) ($a['amount'] ?? 0);
                if ($am > 0 && !empty($a['student_id'])) {
                    $sharedAllocations[] = ['student_id' => (int) $a['student_id'], 'amount' => $am];
                }
            }
            if (empty($sharedAllocations)) {
                return response()->json([
                    'success' => false,
                    'message' => 'When sharing among children, enter at least one amount greater than 0.',
                ], 400);
            }
            $amount = array_sum(array_column($sharedAllocations, 'amount'));
            $firstStudent = Student::find($sharedAllocations[0]['student_id']);
            $isSwimmingLink = (bool) ($paymentLink->metadata['is_swimming'] ?? false);
            $accountRef = $firstStudent
                ? ($isSwimmingLink ? 'SWIM-' . $firstStudent->admission_number : $firstStudent->admission_number)
                : ('FAM-' . $paymentLink->family_id);
            $transaction = PaymentTransaction::create([
                'student_id' => $firstStudent->id,
                'invoice_id' => null,
                'payment_link_id' => $paymentLink->id,
                'gateway' => 'mpesa',
                'reference' => PaymentTransaction::generateReference(),
                'amount' => $amount,
                'currency' => $paymentLink->currency ?? 'KES',
                'status' => 'pending',
                'phone_number' => $request->phone_number,
                'account_reference' => $accountRef,
                'is_shared' => true,
                'shared_allocations' => $sharedAllocations,
            ]);
            try {
                $result = $this->mpesaGateway->initiatePayment($transaction, ['phone_number' => $request->phone_number]);
                if ($result['success']) {
                    $paymentLink->incrementUseCount();
                }
                return response()->json(array_merge($result, ['transaction_id' => $transaction->id, 'payment_link_id' => $paymentLink->id]));
            } catch (\Exception $e) {
                $transaction->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        if ($isFamilyLink && !$isShared) {
            $studentId = $request->student_id;
            $amount = (float) ($request->amount ?? 0);
            if (!$studentId || $amount < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Select a child and enter an amount to pay.',
                ], 400);
            }
            $student = Student::findOrFail($studentId);
            $isSwimmingLink = (bool) ($paymentLink->metadata['is_swimming'] ?? false);
            $accountRef = $isSwimmingLink ? 'SWIM-' . $student->admission_number : $student->admission_number;
            $transaction = PaymentTransaction::create([
                'student_id' => $studentId,
                'invoice_id' => null,
                'payment_link_id' => $paymentLink->id,
                'gateway' => 'mpesa',
                'reference' => PaymentTransaction::generateReference(),
                'amount' => $amount,
                'currency' => $paymentLink->currency ?? 'KES',
                'status' => 'pending',
                'phone_number' => $request->phone_number,
                'account_reference' => $accountRef,
            ]);
            try {
                $result = $this->mpesaGateway->initiatePayment($transaction, ['phone_number' => $request->phone_number]);
                if ($result['success']) {
                    $paymentLink->incrementUseCount();
                }
                return response()->json(array_merge($result, ['transaction_id' => $transaction->id, 'payment_link_id' => $paymentLink->id]));
            } catch (\Exception $e) {
                $transaction->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        // Single-student link
        $amount = (float) ($request->amount ?? $paymentLink->amount);
        if ($amount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid payment amount.',
            ], 400);
        }
        if ($paymentLink->amount > 0 && $amount > $paymentLink->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed KES ' . number_format($paymentLink->amount, 2),
            ], 400);
        }

        try {
            $result = $this->mpesaGateway->initiatePaymentFromLink(
                $paymentLink,
                $request->phone_number,
                $amount
            );
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Payment link processing failed', [
                'payment_link_id' => $paymentLink->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment.',
            ], 500);
        }
    }

    /**
     * Show invoice with Pay Now button
     */
    public function showInvoicePayment(Invoice $invoice)
    {
        $invoice->load(['student', 'items']);

        return view('finance.mpesa.invoice-payment', compact('invoice'));
    }

    /**
     * Process payment from invoice
     */
    public function processInvoicePayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        // Validate phone number
        if (!MpesaGateway::isValidKenyanPhone($request->phone_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number. Please use a valid Kenyan mobile number.',
            ], 400);
        }

        // Use provided amount or invoice balance
        $amount = $request->amount ?? $invoice->balance;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment amount.',
            ], 400);
        }

        try {
            $result = $this->mpesaGateway->initiateAdminPromptedPayment(
                studentId: $invoice->student_id,
                phoneNumber: $request->phone_number,
                amount: $amount,
                invoiceId: $invoice->id,
                adminId: null, // Parent-initiated
                notes: 'Payment from invoice'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment.',
            ], 500);
        }
    }

    /**
     * Show transaction details
     */
    public function showTransaction(PaymentTransaction $transaction)
    {
        $transaction->load(['student', 'invoice', 'paymentLink']);

        return view('finance.mpesa.transaction', compact('transaction'));
    }

    /**
     * Query transaction status
     */
    public function queryTransaction(PaymentTransaction $transaction)
    {
        if ($transaction->gateway !== 'mpesa') {
            return response()->json([
                'success' => false,
                'message' => 'This is not an M-PESA transaction.',
            ], 400);
        }

        try {
            $result = $this->mpesaGateway->queryStkPushStatus($transaction->transaction_id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to query transaction status.',
            ], 500);
        }
    }

    /**
     * Show waiting screen for STK Push
     */
    public function waiting(PaymentTransaction $transaction)
    {
        $transaction->load(['student', 'invoice', 'student.family']);

        return view('finance.mpesa.waiting', compact('transaction'));
    }

    /**
     * Get transaction status (API endpoint for polling)
     */
    public function getTransactionStatus(PaymentTransaction $transaction)
    {
        if ($transaction->gateway !== 'mpesa') {
            return response()->json([
                'status' => 'error',
                'message' => 'This is not an M-PESA transaction.',
            ], 400);
        }

        // Force fresh query from database to get latest status (webhook may have updated it)
        // Don't use refresh() as route model binding might cache the instance
        $transaction = PaymentTransaction::find($transaction->id);
        
        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found.',
            ], 404);
        }

        Log::debug('Transaction status check', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'payment_id' => $transaction->payment_id,
            'updated_at' => $transaction->updated_at,
        ]);

        // Check if already completed/failed/cancelled (webhook may have updated it)
        if (in_array($transaction->status, ['completed', 'failed', 'cancelled'])) {
            $response = [
                'status' => $transaction->status,
                'message' => $transaction->failure_reason ?? 'Transaction ' . $transaction->status,
            ];

            // If completed, include receipt info
            if ($transaction->status === 'completed' && $transaction->payment_id) {
                $payment = Payment::find($transaction->payment_id);
                if ($payment) {
                    $response['receipt_number'] = $payment->receipt_number;
                    $response['receipt_id'] = $payment->id;
                    $response['mpesa_code'] = $transaction->mpesa_receipt_number ?? $transaction->external_transaction_id;
                }
            }

            if ($transaction->status === 'failed') {
                $response['failure_reason'] = $transaction->failure_reason ?? 'Payment failed';
            }

            return response()->json($response);
        }

        // Still processing - rely on webhook for updates (don't query API too early)
        // Only query M-PESA API as fallback after 60 seconds if webhook hasn't arrived
        // This prevents premature failure detection
        
        $secondsSinceCreated = $transaction->created_at->diffInSeconds(now());
        $shouldQueryAPI = $secondsSinceCreated > 60; // Only query after 60 seconds
        
        if (!$transaction->transaction_id) {
            return response()->json([
                'status' => $transaction->status,
                'message' => 'Waiting for M-PESA response...',
            ]);
        }

        // For first 60 seconds, only check database (webhook will update it)
        // Don't query M-PESA API yet as it may return pending status incorrectly
        if (!$shouldQueryAPI) {
            Log::debug('Skipping API query - waiting for webhook', [
                'transaction_id' => $transaction->id,
                'seconds_since_created' => $secondsSinceCreated,
            ]);
            
            return response()->json([
                'status' => 'processing',
                'message' => 'Waiting for payment confirmation...',
            ]);
        }

        // After 60 seconds, query API as fallback (webhook might be delayed)
        try {
            $result = $this->mpesaGateway->queryStkPushStatus($transaction->transaction_id);

            Log::info('M-PESA Query Response (fallback after 60s)', [
                'transaction_id' => $transaction->id,
                'result' => $result,
            ]);

            if ($result['success'] && isset($result['data'])) {
                $data = $result['data'];
                
                // Update transaction based on M-PESA response
                if (isset($data['ResultCode'])) {
                    $resultCode = (int)$data['ResultCode'];
                    $resultDesc = $data['ResultDesc'] ?? '';
                    
                    if ($resultCode == 0) {
                        // Success - process payment
                        // Check if already processed
                        if ($transaction->status !== 'completed') {
                            $this->processSuccessfulPayment($transaction, $data);
                            $transaction->refresh();
                        }
                        
                        $payment = Payment::find($transaction->payment_id);
                        return response()->json([
                            'status' => 'completed',
                            'message' => 'Payment completed successfully',
                            'receipt_number' => $payment->receipt_number ?? null,
                            'receipt_id' => $payment->id ?? null,
                            'mpesa_code' => $transaction->mpesa_receipt_number ?? $transaction->external_transaction_id,
                        ]);
                    } 
                    
                    // Check if it's a pending/waiting status (not a real failure yet)
                    // ResultCode 1032 = request cancelled OR still waiting
                    // ResultCode 1037 = timeout waiting for PIN
                    // Any code with "processing", "pending", "waiting" in description = still pending
                    $pendingCodes = [1032, 1037];
                    $pendingKeywords = ['pending', 'waiting', 'timeout', 'processing', 'under process'];
                    
                    $isPending = in_array($resultCode, $pendingCodes) || 
                                 collect($pendingKeywords)->contains(function($keyword) use ($resultDesc) {
                                     return stripos($resultDesc, $keyword) !== false;
                                 });
                    
                    if ($isPending && $secondsSinceCreated < 120) {
                        // Still waiting for user to enter PIN - don't mark as failed yet
                        Log::debug('M-PESA transaction still pending', [
                            'transaction_id' => $transaction->id,
                            'result_code' => $resultCode,
                            'result_desc' => $resultDesc,
                            'seconds_since_created' => $secondsSinceCreated,
                        ]);
                        
                        return response()->json([
                            'status' => 'processing',
                            'message' => 'Waiting for you to enter your M-PESA PIN',
                        ]);
                    }
                    
                    // After 120 seconds, treat as actual failure
                    // Actual failure - mark as failed
                    $transaction->update([
                        'status' => 'failed',
                        'failure_reason' => $resultDesc ?: 'Payment failed',
                    ]);
                    
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Payment failed',
                        'failure_reason' => $transaction->failure_reason,
                    ]);
                }
            }

            // Still pending - check one more time if webhook updated it
            // Refresh transaction to see if webhook processed it while we were querying
            $transaction = PaymentTransaction::find($transaction->id);
            if (in_array($transaction->status, ['completed', 'failed', 'cancelled'])) {
                $response = [
                    'status' => $transaction->status,
                    'message' => $transaction->failure_reason ?? 'Transaction ' . $transaction->status,
                ];
                
                if ($transaction->status === 'completed' && $transaction->payment_id) {
                    $payment = Payment::find($transaction->payment_id);
                    if ($payment) {
                        $response['receipt_number'] = $payment->receipt_number;
                        $response['receipt_id'] = $payment->id;
                        $response['mpesa_code'] = $transaction->mpesa_receipt_number ?? $transaction->external_transaction_id;
                    }
                }
                
                return response()->json($response);
            }
            
            // Still pending
            return response()->json([
                'status' => $transaction->status,
                'message' => 'Payment is being processed',
            ]);
        } catch (\Exception $e) {
            Log::warning('M-PESA API query failed (non-critical)', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            // Check transaction status one more time in case webhook updated it
            $transaction = PaymentTransaction::find($transaction->id);
            if (in_array($transaction->status, ['completed', 'failed', 'cancelled'])) {
                $response = [
                    'status' => $transaction->status,
                    'message' => $transaction->failure_reason ?? 'Transaction ' . $transaction->status,
                ];
                
                if ($transaction->status === 'completed' && $transaction->payment_id) {
                    $payment = Payment::find($transaction->payment_id);
                    if ($payment) {
                        $response['receipt_number'] = $payment->receipt_number;
                        $response['receipt_id'] = $payment->id;
                        $response['mpesa_code'] = $transaction->mpesa_receipt_number ?? $transaction->external_transaction_id;
                    }
                }
                
                return response()->json($response);
            }

            // API query failed but transaction still processing - return current status
            return response()->json([
                'status' => $transaction->status,
                'message' => 'Checking status...',
            ]);
        }
    }

    /**
     * Cancel transaction (API endpoint)
     */
    public function cancelTransaction(PaymentTransaction $transaction)
    {
        Log::info('Cancel transaction requested', [
            'transaction_id' => $transaction->id,
            'current_status' => $transaction->status,
        ]);

        if (!in_array($transaction->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction cannot be cancelled in its current state.',
                'current_status' => $transaction->status,
            ], 400);
        }

        try {
            $transaction->update([
                'status' => 'cancelled',
                'failure_reason' => 'Cancelled by user',
            ]);

            Log::info('Transaction cancelled successfully', [
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction cancelled successfully',
                'status' => 'cancelled',
            ]);
        } catch (\Exception $e) {
            Log::error('Transaction cancellation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process successful payment - create receipt and allocate payment
     */
    protected function processSuccessfulPayment(PaymentTransaction $transaction, $mpesaData)
    {
        // Skip if already processed
        if ($transaction->payment_id) {
            Log::info('Payment already processed', [
                'transaction_id' => $transaction->id,
                'payment_id' => $transaction->payment_id,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Extract M-PESA receipt number
            // Handle both webhook callback format and query response format
            $mpesaReceiptNumber = null;
            
            // Webhook callback format: CallbackMetadata.Item[]
            if (isset($mpesaData['CallbackMetadata']['Item']) && is_array($mpesaData['CallbackMetadata']['Item'])) {
                foreach ($mpesaData['CallbackMetadata']['Item'] as $item) {
                    if (isset($item['Name']) && $item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceiptNumber = $item['Value'] ?? null;
                        break;
                    }
                }
            }
            
            // Query response format: might have different structure
            // Check if receipt number is directly in the data
            if (!$mpesaReceiptNumber && isset($mpesaData['MpesaReceiptNumber'])) {
                $mpesaReceiptNumber = $mpesaData['MpesaReceiptNumber'];
            }
            
            // Also check for ReceiptNumber (alternative field name)
            if (!$mpesaReceiptNumber && isset($mpesaData['ReceiptNumber'])) {
                $mpesaReceiptNumber = $mpesaData['ReceiptNumber'];
            }
            
            // If still no receipt number, use transaction_id as fallback
            // The receipt number might come later via webhook
            if (!$mpesaReceiptNumber) {
                $mpesaReceiptNumber = $transaction->transaction_id;
            }
            
            Log::info('Processing successful payment', [
                'transaction_id' => $transaction->id,
                'mpesa_receipt_number' => $mpesaReceiptNumber,
                'data_keys' => array_keys($mpesaData),
            ]);

            // Create payment record
            $payment = Payment::create([
                'student_id' => $transaction->student_id,
                'invoice_id' => $transaction->invoice_id,
                'amount' => $transaction->amount,
                'payment_method' => 'mpesa',
                'payment_date' => now(),
                'receipt_number' => 'REC-' . strtoupper(Str::random(10)),
                'transaction_id' => $mpesaReceiptNumber ?? $transaction->transaction_id,
                'status' => 'approved',
                'notes' => 'M-PESA STK Push payment',
                'created_by' => $transaction->initiated_by,
            ]);

            // Update transaction
            $transaction->update([
                'status' => 'completed',
                'payment_id' => $payment->id,
                'external_transaction_id' => $mpesaReceiptNumber,
                'completed_at' => now(),
            ]);

            // Allocate payment to invoice items
            $this->allocatePaymentToInvoices($payment, $transaction);

            DB::commit();

            // Refresh so receipt and notification use allocated amounts and relations
            $payment->refresh();
            $payment->load(['allocations.invoiceItem.invoice', 'student']);

            // Generate receipt PDF (outside transaction to avoid locking)
            try {
                $receiptService = app(\App\Services\ReceiptService::class);
                $pdfPath = $receiptService->generateReceipt($payment, ['save' => true]);
                Log::info('Receipt generated for M-PESA payment', [
                    'payment_id' => $payment->id,
                    'pdf_path' => $pdfPath,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate receipt for M-PESA payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send payment confirmation (SMS/email via RKS Finance, same as other payments)
            try {
                $this->sendPaymentConfirmation($payment);
            } catch (\Exception $e) {
                Log::error('Failed to send payment confirmation', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Payment processed successfully', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process successful payment', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Allocate payment to invoice items (payment_allocations require invoice_item_id).
     * Uses PaymentAllocationService so allocations and invoice recalc are consistent.
     */
    protected function allocatePaymentToInvoices(Payment $payment, PaymentTransaction $transaction)
    {
        // Swimming payments are handled by wallet, not invoice items
        if (strpos($payment->receipt_number ?? '', 'SWIM-') === 0) {
            $payment->update([
                'allocated_amount' => $payment->amount,
                'unallocated_amount' => 0,
            ]);
            return;
        }

        $studentId = $transaction->student_id;
        $remainingAmount = (float) $payment->amount;
        $allocations = [];

        // Helper: get unpaid invoice items for an invoice (or for student), sorted by invoice issued_date
        $getUnpaidItems = function (?int $invoiceId = null) use ($studentId) {
            $q = InvoiceItem::whereHas('invoice', function ($qb) use ($studentId, $invoiceId) {
                $qb->where('student_id', $studentId);
                if ($invoiceId !== null) {
                    $qb->where('id', $invoiceId);
                }
            })
                ->where('status', 'active')
                ->with(['invoice'])
                ->get()
                ->filter(function ($item) {
                    return $item->getBalance() > 0;
                });
            return $q->sortBy('invoice.issued_date')->values();
        };

        // If transaction has a specific invoice, allocate to its items first
        if ($transaction->invoice_id && $remainingAmount > 0) {
            $items = $getUnpaidItems($transaction->invoice_id);
            foreach ($items as $item) {
                if ($remainingAmount <= 0) {
                    break;
                }
                $balance = $item->getBalance();
                $amount = min($remainingAmount, $balance);
                if ($amount > 0) {
                    $allocations[] = ['invoice_item_id' => $item->id, 'amount' => $amount];
                    $remainingAmount -= $amount;
                }
            }
        }

        // Allocate remainder to oldest unpaid items (any invoice for this student)
        if ($remainingAmount > 0) {
            $items = $getUnpaidItems(null);
            foreach ($items as $item) {
                if ($remainingAmount <= 0) {
                    break;
                }
                if ($transaction->invoice_id && $item->invoice_id == $transaction->invoice_id) {
                    continue; // already considered above
                }
                $balance = $item->getBalance();
                $amount = min($remainingAmount, $balance);
                if ($amount > 0) {
                    $allocations[] = ['invoice_item_id' => $item->id, 'amount' => $amount];
                    $remainingAmount -= $amount;
                }
            }
        }

        if (!empty($allocations)) {
            $this->allocationService->allocatePayment($payment, $allocations);
        } else {
            $payment->update([
                'allocated_amount' => 0,
                'unallocated_amount' => $payment->amount,
            ]);
        }

        if ($remainingAmount > 0.01) {
            Log::info('Overpayment detected', [
                'payment_id' => $payment->id,
                'overpayment_amount' => $remainingAmount,
            ]);
        }
    }

    /**
     * Cancel/expire payment link
     */
    public function cancelLink(PaymentLink $paymentLink)
    {
        $paymentLink->status = 'cancelled';
        $paymentLink->save();

        return back()->with('success', 'Payment link cancelled successfully.');
    }

    /**
     * Send payment link via SMS/Email
     */
    public function sendLink(Request $request, PaymentLink $paymentLink)
    {
        $request->validate([
            'channels' => 'required|array',
            'channels.*' => 'in:sms,email',
        ]);

        try {
            $student = $paymentLink->student;
            $parent = $student->family;

            $message = "Dear Parent,\n\n";
            $message .= "Please pay KES " . number_format($paymentLink->amount, 2) . " ";
            $message .= "for {$student->first_name} {$student->last_name}.\n\n";
            $message .= "Pay here: " . $paymentLink->getShortUrl() . "\n\n";
            if ($paymentLink->expires_at) {
                $message .= "Link expires: " . $paymentLink->expires_at->format('d M Y') . "\n";
            }
            $message .= "\nThank you.";

            $commService = app(\App\Services\CommunicationService::class);
            $sent = [];

            if (in_array('sms', $request->channels) && $parent->phone) {
                $commService->sendSMS('parent', $parent->id, $parent->phone, $message, 'Payment Link');
                $sent[] = 'SMS';
            }

            if (in_array('email', $request->channels) && $parent->primary_email) {
                $htmlMessage = nl2br($message);
                $commService->sendEmail('parent', $parent->id, $parent->primary_email, 'School Fee Payment Link', $htmlMessage);
                $sent[] = 'Email';
            }

            if (empty($sent)) {
                return back()->with('error', 'No valid contact information found.');
            }

            return back()->with('success', 'Payment link sent via ' . implode(' and ', $sent));
        } catch (\Exception $e) {
            Log::error('Failed to send payment link', [
                'link_id' => $paymentLink->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send payment link.');
        }
    }

    /**
     * Send payment notifications via selected channels
     */
    /**
     * Format error message for user display
     */
    protected function formatErrorMessage(string $message): string
    {
        // Make error messages more user-friendly
        $userFriendlyErrors = [
            'M-PESA authentication failed' => 'M-PESA authentication failed. Please contact administrator to verify credentials.',
            'Invalid Access Token' => 'M-PESA service configuration error. Please contact administrator.',
            'Network error' => 'Unable to connect to M-PESA. Please check your internet connection and try again.',
            'Failed to authenticate' => 'M-PESA authentication failed. Please contact administrator.',
        ];

        foreach ($userFriendlyErrors as $key => $friendly) {
            if (stripos($message, $key) !== false) {
                return $friendly;
            }
        }

        return $message;
    }

    protected function sendPaymentNotifications($student, $amount, $channels, $message)
    {
        if (!$student->family) {
            return;
        }

        $family = $student->family;
        $studentName = $student->first_name . ' ' . $student->last_name;
        $amountFormatted = number_format($amount, 2);

        // SMS
        if (in_array('sms', $channels) && $family->phone) {
            $smsMessage = "Dear Parent, $message for $studentName. Amount: KES $amountFormatted. Royal Kings School";
            $this->smsService->sendSMS(
                $family->phone,
                $smsMessage,
                'RKS_FINANCE'  // Use RKS_FINANCE sender ID
            );
        }

        // Email
        if (in_array('email', $channels) && $family->email) {
            $subject = "M-PESA Payment Notification - $studentName";
            $emailBody = "<p>Dear Parent,</p>
                <p>$message for <strong>$studentName</strong> (Admission: {$student->admission_number}).</p>
                <p><strong>Amount:</strong> KES $amountFormatted</p>
                <p>Please complete the payment via M-PESA.</p>
                <p>Thank you,<br>Royal Kings School</p>";
            
            $this->emailService->sendEmail(
                $family->email,
                $subject,
                $emailBody
            );
        }

        // WhatsApp - prioritize WhatsApp fields, fallback to father/mother phone
        if (in_array('whatsapp', $channels)) {
            $parent = $student->parent;
            $whatsappPhone = null;
            
            if ($parent) {
                // Never send fee-related communications to guardian; guardians are reached via manual number entry only
                $whatsappPhone = !empty($parent->father_whatsapp) ? $parent->father_whatsapp 
                    : (!empty($parent->mother_whatsapp) ? $parent->mother_whatsapp 
                    : (!empty($parent->father_phone) ? $parent->father_phone 
                    : (!empty($parent->mother_phone) ? $parent->mother_phone : null)));
            }
            
            if ($whatsappPhone) {
                $whatsappMessage = "*M-PESA Payment Notification*\n\n";
                $whatsappMessage .= "Dear Parent,\n\n";
                $whatsappMessage .= "$message for *$studentName*\n";
                $whatsappMessage .= "Amount: *KES $amountFormatted*\n\n";
                $whatsappMessage .= "Thank you,\nRoyal Kings School";
                
                try {
                    $this->whatsappService->sendMessage(
                        $whatsappPhone,
                        $whatsappMessage
                    );
                } catch (\Exception $e) {
                    Log::warning('WhatsApp send failed', ['phone' => $whatsappPhone, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Send payment link via selected channels to selected parents
     */
    protected function sendPaymentLinkToParents($student, $paymentLink, $channels, $parents)
    {
        // Load family and parent info
        $student->load(['family', 'parentInfo']);
        
        if (!$student->family) {
            Log::warning('Cannot send payment link: student has no family', ['student_id' => $student->id]);
            return;
        }

        $family = $student->family;
        $studentName = $student->first_name . ' ' . $student->last_name;
        $amountFormatted = number_format($paymentLink->amount, 2);
        $linkUrl = $paymentLink->getPaymentUrl();

        // Prepare parent contacts
        $contacts = [];
        
        // Load parent info if available (for WhatsApp numbers)
        $parentInfo = $student->parentInfo ?? null;
        
        if (in_array('father', $parents)) {
            // Try to get WhatsApp number from ParentInfo, fallback to family phone
            $phone = $family->father_phone;
            if ($parentInfo && $parentInfo->father_whatsapp) {
                $phone = $parentInfo->father_whatsapp;
            } elseif ($parentInfo && $parentInfo->father_phone) {
                $phone = $parentInfo->father_phone;
            }
            
            if ($phone) {
                $contacts[] = [
                    'name' => $family->father_name ?? 'Father',
                    'phone' => $phone,
                    'email' => $family->father_email ?? ($parentInfo->father_email ?? null),
                ];
            }
        }
        
        if (in_array('mother', $parents)) {
            // Try to get WhatsApp number from ParentInfo, fallback to family phone
            $phone = $family->mother_phone;
            if ($parentInfo && $parentInfo->mother_whatsapp) {
                $phone = $parentInfo->mother_whatsapp;
            } elseif ($parentInfo && $parentInfo->mother_phone) {
                $phone = $parentInfo->mother_phone;
            }
            
            if ($phone) {
                $contacts[] = [
                    'name' => $family->mother_name ?? 'Mother',
                    'phone' => $phone,
                    'email' => $family->mother_email ?? ($parentInfo->mother_email ?? null),
                ];
            }
        }
        
        if (in_array('primary', $parents)) {
            $phone = $family->phone;
            // Try to get WhatsApp from ParentInfo if available
            if ($parentInfo) {
                $phone = $parentInfo->primary_contact_phone ?? $parentInfo->father_phone ?? $parentInfo->mother_phone ?? $family->phone;
            }
            
            if ($phone) {
                $contacts[] = [
                    'name' => 'Parent',
                    'phone' => $phone,
                    'email' => $family->email ?? ($parentInfo->primary_contact_email ?? null),
                ];
            }
        }

        // Send to each contact
        foreach ($contacts as $contact) {
            // SMS
            if (in_array('sms', $channels) && !empty($contact['phone'])) {
                $smsMessage = "Dear {$contact['name']}, Pay KES $amountFormatted for $studentName. Click: $linkUrl - Royal Kings School";
                try {
                    $this->smsService->sendSMS(
                        $contact['phone'],
                        $smsMessage,
                        'RKS_FINANCE'
                    );
                } catch (\Exception $e) {
                    Log::warning('SMS send failed', ['phone' => $contact['phone'], 'error' => $e->getMessage()]);
                }
            }

            // Email
            if (in_array('email', $channels) && !empty($contact['email'])) {
                $subject = "Payment Link - $studentName";
                $emailBody = "<p>Dear {$contact['name']},</p>
                    <p>A payment link has been generated for <strong>$studentName</strong> (Admission: {$student->admission_number}).</p>
                    <p><strong>Amount:</strong> KES $amountFormatted</p>
                    <p><strong>Description:</strong> {$paymentLink->description}</p>
                    <p><a href='$linkUrl' style='background: #0f766e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Pay Now</a></p>
                    " . ($paymentLink->expires_at ? "<p><em>Link expires: {$paymentLink->expires_at->format('d M Y H:i')}</em></p>" : "") . "
                    <p>Thank you,<br>Royal Kings School</p>";
                
                try {
                    $this->emailService->sendEmail(
                        $contact['email'],
                        $subject,
                        $emailBody
                    );
                } catch (\Exception $e) {
                    Log::warning('Email send failed', ['email' => $contact['email'], 'error' => $e->getMessage()]);
                }
            }

            // WhatsApp
            if (in_array('whatsapp', $channels) && !empty($contact['phone'])) {
                $whatsappMessage = "*Payment Link - Royal Kings School*\n\n";
                $whatsappMessage .= "Dear {$contact['name']},\n\n";
                $whatsappMessage .= "Pay *KES $amountFormatted* for *$studentName*\n";
                $whatsappMessage .= "({$paymentLink->description})\n\n";
                $whatsappMessage .= "Click to pay: $linkUrl\n\n";
                if ($paymentLink->expires_at) {
                    $whatsappMessage .= "Link expires: {$paymentLink->expires_at->format('d M Y H:i')}\n\n";
                }
                $whatsappMessage .= "Thank you,\nRoyal Kings School";
                
                try {
                    $this->whatsappService->sendMessage(
                        $contact['phone'],
                        $whatsappMessage
                    );
                } catch (\Exception $e) {
                    Log::warning('WhatsApp send failed', ['phone' => $contact['phone'], 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Get student data (API endpoint)
     */
    public function getStudentData(Student $student)
    {
        $student->load(['family', 'classroom', 'parent']);
        
        $familyData = null;
        $parentInfo = $student->parent;
        if ($student->family) {
            $familyData = [
                'id' => $student->family->id,
                'phone' => $student->family->phone,
                'email' => $student->family->email,
                'father_name' => $student->family->father_name,
                'father_phone' => $student->family->father_phone,
                'father_email' => $student->family->father_email,
                'mother_name' => $student->family->mother_name,
                'mother_phone' => $student->family->mother_phone,
                'mother_email' => $student->family->mother_email,
            ];
        } elseif ($parentInfo) {
            $familyData = [
                'id' => null,
                'phone' => $parentInfo->primary_contact_phone,
                'email' => $parentInfo->primary_contact_email,
                'father_name' => $parentInfo->father_name,
                'father_phone' => $parentInfo->father_phone,
                'father_email' => $parentInfo->father_email,
                'mother_name' => $parentInfo->mother_name,
                'mother_phone' => $parentInfo->mother_phone,
                'mother_email' => $parentInfo->mother_email,
                'guardian_name' => $parentInfo->guardian_name,
                'guardian_phone' => $parentInfo->guardian_phone,
                'guardian_email' => $parentInfo->guardian_email,
            ];
        }

        if ($familyData && $parentInfo) {
            $familyData['father_whatsapp'] = $parentInfo->father_whatsapp ?? $familyData['father_whatsapp'] ?? null;
            $familyData['mother_whatsapp'] = $parentInfo->mother_whatsapp ?? $familyData['mother_whatsapp'] ?? null;
            $familyData['guardian_whatsapp'] = $parentInfo->guardian_whatsapp ?? $familyData['guardian_whatsapp'] ?? null;
            $familyData['guardian_phone'] = $familyData['guardian_phone'] ?? $parentInfo->guardian_phone ?? null;
            $familyData['father_phone'] = $familyData['father_phone'] ?? $parentInfo->father_phone ?? null;
            $familyData['mother_phone'] = $familyData['mother_phone'] ?? $parentInfo->mother_phone ?? null;
            $familyData['father_email'] = $familyData['father_email'] ?? $parentInfo->father_email ?? null;
            $familyData['mother_email'] = $familyData['mother_email'] ?? $parentInfo->mother_email ?? null;
            $familyData['phone'] = $familyData['phone'] ?? $parentInfo->primary_contact_phone;
            $familyData['email'] = $familyData['email'] ?? $parentInfo->primary_contact_email;
        }
        
        if (!$familyData) {
            Log::warning('Student API: no parent contact data found', [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'family_id' => $student->family_id,
                'parent_id' => $student->parent_id,
            ]);
        }
        
        // Fee balance (total outstanding from invoices)
        $feeBalance = (float) \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
        $swimmingBalance = (float) (\App\Models\SwimmingWallet::getOrCreateForStudent($student->id)->balance ?? 0);

        // Siblings (same family, exclude self) with fee balance each
        $siblings = [];
        if ($student->family_id) {
            $siblings = Student::where('family_id', $student->family_id)
                ->where('id', '!=', $student->id)
                ->whereNotNull('family_id')
                ->with('classroom')
                ->get()
                ->map(function ($s) {
                    $bal = (float) \App\Services\StudentBalanceService::getTotalOutstandingBalance($s);
                    $cr = $s->classroom;
                    $classroomName = (is_object($cr) && isset($cr->name)) ? $cr->name : null;
                    return [
                        'id' => $s->id,
                        'full_name' => $s->full_name ?? trim($s->first_name . ' ' . $s->last_name),
                        'admission_number' => $s->admission_number,
                        'classroom_name' => $classroomName,
                        'fee_balance' => round($bal, 2),
                    ];
                })
                ->values()
                ->toArray();
        }

        $classroom = $student->classroom;
        $classroomName = (is_object($classroom) && isset($classroom->name)) ? $classroom->name : null;
        $classroomPayload = (is_object($classroom) && isset($classroom->id)) ? [
            'id' => $classroom->id,
            'name' => $classroomName,
        ] : null;

        return response()->json([
            'id' => $student->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'full_name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'classroom_id' => $student->classroom_id,
            'classroom_name' => $classroomName,
            'family_id' => $student->family_id,
            'classroom' => $classroomPayload,
            'family' => $familyData,
            'fee_balance' => round($feeBalance, 2),
            'swimming_balance' => round($swimmingBalance, 2),
            'siblings' => $siblings,
        ]);
    }

    /**
     * Get student invoices (API endpoint)
     */
    public function getStudentInvoices(Student $student)
    {
        $invoices = Invoice::where('student_id', $student->id)
            ->with(['academicYear', 'term'])
            ->orderBy('due_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                // Calculate balance if not already set (Invoice uses total and paid_amount)
                $balance = $invoice->balance ?? (float) ($invoice->total ?? 0) - (float) ($invoice->paid_amount ?? 0);
                $ay = $invoice->academicYear;
                $term = $invoice->term;
                $academicYearName = (is_object($ay) && isset($ay->name)) ? $ay->name : null;
                $termName = (is_object($term) && isset($term->name)) ? $term->name : null;

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => (float) ($invoice->total ?? 0),
                    'amount_paid' => (float) ($invoice->paid_amount ?? 0),
                    'balance' => max(0, (float) $balance), // Ensure balance is not negative
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'academic_year' => $academicYearName,
                    'term' => $termName,
                ];
            })
            ->filter(function ($invoice) {
                // Only return invoices with outstanding balance
                return $invoice['balance'] > 0;
            })
            ->values(); // Re-index array

        return response()->json($invoices);
    }

    /**
     * C2B Webhook handler (receives all paybill transactions)
     * This is called by M-PESA when a customer pays via paybill
     */
    public function handleC2BCallback(Request $request)
    {
        try {
            Log::info('M-PESA C2B Callback received', ['data' => $request->all()]);

            // Check if table exists
            if (!Schema::hasTable('mpesa_c2b_transactions')) {
                Log::error('M-PESA C2B Callback: Table does not exist', [
                    'message' => 'mpesa_c2b_transactions table not found. Please run migrations.',
                ]);
                
                // Still return success to M-PESA to avoid retries
                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Accepted',
                ]);
            }

            $data = $request->all();

            // Extract transaction details
            $transId = $data['TransID'] ?? $data['trans_id'] ?? null;
            $transTime = $data['TransTime'] ?? $data['trans_time'] ?? null;
            $transAmount = $data['TransAmount'] ?? $data['trans_amount'] ?? 0;
            $businessShortCode = $data['BusinessShortCode'] ?? $data['business_short_code'] ?? null;
            $billRefNumber = $data['BillRefNumber'] ?? $data['bill_ref_number'] ?? null;
            $invoiceNumber = $data['InvoiceNumber'] ?? $data['invoice_number'] ?? null;
            $msisdn = $data['MSISDN'] ?? $data['msisdn'] ?? null;
            $firstName = $data['FirstName'] ?? $data['first_name'] ?? null;
            $middleName = $data['MiddleName'] ?? $data['middle_name'] ?? null;
            $lastName = $data['LastName'] ?? $data['last_name'] ?? null;
            $transactionType = $data['TransactionType'] ?? 'Paybill';

            // Create C2B transaction record
            $c2bTransaction = MpesaC2BTransaction::create([
                'transaction_type' => $transactionType,
                'trans_id' => $transId,
                'trans_time' => $this->parseMpesaTime($transTime),
                'trans_amount' => $transAmount,
                'business_short_code' => $businessShortCode,
                'bill_ref_number' => $billRefNumber,
                'invoice_number' => $invoiceNumber,
                'org_account_balance' => $data['OrgAccountBalance'] ?? null,
                'third_party_trans_id' => $data['ThirdPartyTransID'] ?? null,
                'msisdn' => $msisdn,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'unallocated_amount' => $transAmount,
                'raw_data' => $data,
            ]);

            // Check for duplicates (including cross-type with bank statements)
            $isDuplicate = $c2bTransaction->checkForDuplicate();
            
            // Also check against bank statement transactions
            if (!$isDuplicate) {
                $unifiedService = app(\App\Services\UnifiedTransactionService::class);
                $crossDuplicate = $unifiedService->checkDuplicateAcrossTypes(
                    $transId,
                    $transAmount,
                    $msisdn,
                    $c2bTransaction->trans_time,
                    $c2bTransaction->id,
                    'c2b'
                );
                
                if ($crossDuplicate) {
                    $isDuplicate = true;
                    $c2bTransaction->markAsDuplicate($crossDuplicate['id']);
                }
            }
            
            if (!$isDuplicate) {
                // Attempt smart matching using unified service
                $unifiedService = app(\App\Services\UnifiedTransactionService::class);
                $unifiedService->matchC2BTransaction($c2bTransaction);
            }

            Log::info('C2B transaction created', [
                'id' => $c2bTransaction->id,
                'trans_id' => $transId,
                'amount' => $transAmount,
                'is_duplicate' => $isDuplicate,
            ]);

            // Return success response to M-PESA
            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        } catch (\Exception $e) {
            Log::error('C2B callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            // Still return success to M-PESA to avoid retries
            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        }
    }

    /**
     * C2B Transactions Dashboard
     */
    public function c2bDashboard(Request $request)
    {
        // Check if table exists, if not return empty stats
        if (!Schema::hasTable('mpesa_c2b_transactions')) {
            $stats = [
                'today_count' => 0,
                'today_amount' => 0,
                'unallocated_count' => 0,
                'unallocated_amount' => 0,
                'auto_matched_count' => 0,
                'duplicates_count' => 0,
            ];

            $unallocatedTransactions = collect();

            return view('finance.mpesa.c2b-dashboard', compact('stats', 'unallocatedTransactions'))
                ->with('warning', 'C2B transactions table does not exist. Please run migrations: php artisan migrate');
        }

        $stats = [
            'today_count' => MpesaC2BTransaction::today()->count(),
            'today_amount' => MpesaC2BTransaction::today()->sum('trans_amount'),
            'unallocated_count' => MpesaC2BTransaction::unallocated()->count(),
            'unallocated_amount' => MpesaC2BTransaction::unallocated()->sum('trans_amount'),
            'auto_matched_count' => MpesaC2BTransaction::where('allocation_status', 'auto_matched')->today()->count(),
            'duplicates_count' => MpesaC2BTransaction::where('is_duplicate', true)->today()->count(),
        ];

        // Get recent unallocated transactions
        $unallocatedTransactions = MpesaC2BTransaction::unallocated()
            ->with(['student', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('finance.mpesa.c2b-dashboard', compact('stats', 'unallocatedTransactions'));
    }

    /**
     * View all C2B transactions
     */
    public function c2bTransactions(Request $request)
    {
        $query = MpesaC2BTransaction::with(['student', 'invoice', 'payment', 'processedBy']);

        // Filters
        if ($request->filled('status')) {
            $query->where('allocation_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trans_id', 'LIKE', "%$search%")
                  ->orWhere('bill_ref_number', 'LIKE', "%$search%")
                  ->orWhere('msisdn', 'LIKE', "%$search%")
                  ->orWhere('first_name', 'LIKE', "%$search%")
                  ->orWhere('last_name', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('trans_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trans_time', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('finance.mpesa.c2b-transactions', compact('transactions'));
    }

    /**
     * Show single C2B transaction for allocation
     * Redirects to unified bank-statements view
     */
    public function c2bTransactionShow($id)
    {
        // Redirect to unified bank-statements view
        return redirect()->route('finance.bank-statements.show', $id);
    }

    /**
     * Allocate C2B transaction to student/invoice
     * Redirects to unified bank-statements view (allocation happens there)
     */
    public function c2bAllocate(Request $request, $id)
    {
        $isSwimming = $request->boolean('is_swimming_transaction', false);
        
        if ($isSwimming) {
            // Validation for swimming transactions (sibling allocations)
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required|in:mpesa',
                'sibling_allocations' => 'required|array|min:1',
                'sibling_allocations.*.student_id' => 'required|exists:students,id',
                'sibling_allocations.*.amount' => 'required|numeric|min:0.01',
            ]);
        } else {
            // Validation for regular transactions (invoice allocations)
            // Allocations are optional - if no invoices exist, it's an advance payment
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required|in:mpesa',
                'allocations' => 'nullable|array',
                'allocations.*.invoice_id' => 'required_with:allocations|exists:invoices,id',
                'allocations.*.amount' => 'required_with:allocations|numeric|min:1',
            ]);
        }

        $transaction = MpesaC2BTransaction::findOrFail($id);
        $transactionDate = \Carbon\Carbon::parse($transaction->trans_time);
        if ($transactionDate->gt(now()->endOfDay())) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Cannot create payment with a future payment date (' . $transactionDate->format('d M Y') . ').'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Mark transaction as swimming if applicable
            if ($isSwimming) {
                $transaction->update(['is_swimming_transaction' => true]);
            }

            if ($isSwimming) {
                // Handle swimming transaction - allocate to wallet balances
                $totalAllocated = 0;
                $payments = [];
                
                foreach ($request->sibling_allocations as $allocation) {
                    $student = Student::findOrFail($allocation['student_id']);
                    $allocationAmount = (float) $allocation['amount'];
                    
                    if ($allocationAmount <= 0) {
                        continue;
                    }
                    
                    $totalAllocated += $allocationAmount;
                    
                    // Create payment record for this student
                    $payment = Payment::create([
                        'student_id' => $student->id,
                        'amount' => $allocationAmount,
                        'payment_method' => 'mpesa',
                        'payment_date' => $transaction->trans_time,
                        'receipt_number' => 'REC-' . strtoupper(Str::random(10)),
                        'transaction_id' => $transaction->trans_id,
                        'status' => 'approved',
                        'notes' => 'M-PESA Paybill swimming payment - ' . $transaction->full_name . ' (Shared: ' . count($request->sibling_allocations) . ' students)',
                        'created_by' => Auth::id(),
                    ]);
                    
                    $payments[] = $payment;
                    
                    // Credit swimming wallet
                    $this->swimmingWalletService->creditFromTransaction(
                        $student,
                        $payment,
                        $allocationAmount,
                        "Swimming payment from M-PESA transaction #{$transaction->trans_id}"
                    );
                    
                    // Mark payment as fully allocated (goes to wallet, not invoices)
                    $payment->update([
                        'allocated_amount' => $allocationAmount,
                        'unallocated_amount' => 0,
                    ]);
                }
                
                // Update C2B transaction with primary student (first one)
                $primaryStudent = Student::findOrFail($request->student_id);
                $primaryPayment = $payments[0] ?? null;
                
                if ($primaryPayment) {
                    $transaction->allocate($primaryStudent, null, $primaryPayment, $totalAllocated);
                }
                
                $receiptNumbers = implode(', ', array_map(fn($p) => $p->receipt_number, $payments));
                
            } else {
                // Handle regular transaction - allocate to invoices
                $student = Student::findOrFail($request->student_id);

                // Create payment record
                $payment = Payment::create([
                    'student_id' => $student->id,
                    'amount' => $request->amount,
                    'payment_method' => 'mpesa',
                    'payment_date' => $transaction->trans_time,
                    'receipt_number' => 'REC-' . strtoupper(Str::random(10)),
                    'transaction_id' => $transaction->trans_id,
                    'status' => 'approved',
                    'notes' => 'M-PESA Paybill payment - ' . $transaction->full_name,
                    'created_by' => Auth::id(),
                ]);

                // Allocate to invoice items (payment_allocations require invoice_item_id)
                if (!empty($request->allocations) && is_array($request->allocations)) {
                    $allocationsForService = [];
                    foreach ($request->allocations as $allocation) {
                        $invoice = Invoice::findOrFail($allocation['invoice_id']);
                        $remaining = (float) $allocation['amount'];
                        $items = $invoice->items()->where('status', 'active')->with('invoice')->get()
                            ->filter(fn ($item) => $item->getBalance() > 0)
                            ->sortBy('invoice.issued_date')->values();
                        foreach ($items as $item) {
                            if ($remaining <= 0) break;
                            $balance = $item->getBalance();
                            $amount = min($remaining, $balance);
                            if ($amount > 0) {
                                $allocationsForService[] = ['invoice_item_id' => $item->id, 'amount' => $amount];
                                $remaining -= $amount;
                            }
                        }
                    }
                    if (!empty($allocationsForService)) {
                        $this->allocationService->allocatePayment($payment, $allocationsForService);
                    }
                }
                // If no allocations, payment is recorded as advance payment (unallocated_amount will be the full amount)

                // Update C2B transaction
                $transaction->allocate($student, null, $payment, $request->amount);
                
                $receiptNumbers = $payment->receipt_number;
            }

            DB::commit();

            Log::info('C2B transaction allocated', [
                'transaction_id' => $transaction->id,
                'is_swimming' => $isSwimming,
                'student_id' => $request->student_id,
                'amount' => $request->amount,
            ]);

            // Prepare detailed response data
            $responseData = [
                'success' => true,
                'message' => $isSwimming 
                    ? "Swimming payment allocated successfully!"
                    : "Transaction allocated successfully!",
                'details' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transaction->trans_id,
                    'amount' => number_format($request->amount, 2),
                    'is_swimming' => $isSwimming,
                    'student_id' => $request->student_id,
                ]
            ];

            if ($isSwimming) {
                $responseData['details']['students_count'] = count($request->sibling_allocations);
                $responseData['details']['receipt_numbers'] = explode(', ', $receiptNumbers);
                $responseData['details']['students'] = array_map(function($allocation) {
                    $student = Student::find($allocation['student_id']);
                    return [
                        'id' => $student->id,
                        'name' => $student->full_name,
                        'admission_number' => $student->admission_number,
                        'amount' => number_format($allocation['amount'], 2),
                    ];
                }, $request->sibling_allocations);
            } else {
                $student = Student::findOrFail($request->student_id);
                $responseData['details']['student_name'] = $student->full_name;
                $responseData['details']['student_admission'] = $student->admission_number;
                $responseData['details']['receipt_number'] = $receiptNumbers;
                $responseData['details']['allocations_count'] = count($request->allocations ?? []);
                $responseData['details']['is_advance_payment'] = empty($request->allocations);
            }

            // Return JSON for AJAX requests, redirect for regular form submissions
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($responseData);
            }

            $message = $isSwimming 
                ? "Swimming payment allocated to " . count($request->sibling_allocations) . " student(s) successfully! Receipt(s): " . $receiptNumbers
                : "Transaction allocated successfully! Receipt: " . $receiptNumbers;

            // Redirect to unified bank-statements view
            $receiptIds = [];
            if ($isSwimming && !empty($payments)) {
                $receiptIds = collect($payments)->pluck('id')->toArray();
            } elseif (isset($payment) && $payment) {
                $receiptIds = [$payment->id];
            }
            
            $redirect = redirect()
                ->route('finance.bank-statements.show', $id)
                ->with('success', $message);
            if (!empty($receiptIds)) {
                $redirect->with('receipt_ids', array_values(array_unique($receiptIds)));
            }
            
            return $redirect;
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            
            Log::error('C2B allocation validation failed', [
                'transaction_id' => $id,
                'errors' => $e->errors(),
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_type' => 'validation'
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($errorResponse, 422);
            }

            // Redirect to unified view with errors
            return redirect()
                ->route('finance.bank-statements.show', $id)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('C2B allocation failed', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Failed to allocate transaction',
                'error' => $e->getMessage(),
                'error_type' => 'server_error'
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($errorResponse, 500);
            }

            // Redirect to unified view with error
            return redirect()
                ->route('finance.bank-statements.show', $id)
                ->with('error', 'Failed to allocate transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get latest C2B transactions (API for real-time updates)
     */
    public function getLatestC2BTransactions(Request $request)
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('mpesa_c2b_transactions')) {
                return response()->json([
                    'error' => 'Table does not exist',
                    'message' => 'Please run migrations: php artisan migrate',
                    'transactions' => []
                ], 200);
            }

            $since = $request->input('since', now()->subMinutes(5));
            
            // Parse the since parameter if it's a string
            if (is_string($since)) {
                try {
                    $since = Carbon::parse($since);
                } catch (\Exception $e) {
                    $since = now()->subMinutes(5);
                }
            }
            
            $transactions = MpesaC2BTransaction::where('created_at', '>=', $since)
                ->with(['student'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'trans_id' => $transaction->trans_id,
                        'trans_time' => $transaction->trans_time ? $transaction->trans_time->format('Y-m-d H:i:s') : null,
                        'amount' => number_format($transaction->trans_amount, 2),
                        'payer_name' => $transaction->full_name,
                        'phone' => $transaction->formatted_phone,
                        'reference' => $transaction->bill_ref_number,
                        'student_name' => $transaction->student ? $transaction->student->first_name . ' ' . $transaction->student->last_name : null,
                        'allocation_status' => $transaction->allocation_status,
                        'match_confidence' => $transaction->match_confidence,
                        'is_duplicate' => $transaction->is_duplicate,
                    ];
                });

            return response()->json($transactions);
        } catch (\Exception $e) {
            Log::error('Failed to get latest C2B transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch transactions',
                'message' => $e->getMessage(),
                'transactions' => []
            ], 500);
        }
    }

    /**
     * Register C2B URLs with M-PESA
     * This registers the validation and confirmation URLs for C2B payments
     */
    public function registerC2BUrls(Request $request)
    {
        try {
            // Get URLs from request or use config defaults
            // Note: Use route without "mpesa" in path (Safaricom requirement)
            $confirmationUrl = $request->input('confirmation_url') 
                ?? config('mpesa.confirmation_url')
                ?? route('payment.webhook.c2b');
            
            $validationUrl = $request->input('validation_url') 
                ?? config('mpesa.validation_url')
                ?? route('payment.webhook.c2b');
            
            $responseType = $request->input('response_type') 
                ?? config('mpesa.c2b.response_type', 'Completed');

            // Validate response type
            if (!in_array($responseType, ['Completed', 'Cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ResponseType must be either "Completed" or "Cancelled"',
                ], 400);
            }

            // Register URLs
            $result = $this->mpesaGateway->registerC2BUrls(
                $confirmationUrl,
                $validationUrl,
                $responseType
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'C2B URLs registered successfully',
                    'data' => [
                        'confirmation_url' => $confirmationUrl,
                        'validation_url' => $validationUrl,
                        'response_type' => $responseType,
                        'originator_conversation_id' => $result['originator_conversation_id'] ?? null,
                    ],
                    'response' => $result['response'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to register C2B URLs',
                'error' => $result['error'] ?? null,
            ], $result['status_code'] ?? 500);

        } catch (\Exception $e) {
            Log::error('C2B URL registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register C2B URLs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse M-PESA timestamp
     */
    protected function parseMpesaTime($timeString)
    {
        try {
            // M-PESA format: YYYYMMDDHHmmss
            if (is_numeric($timeString) && strlen($timeString) == 14) {
                return Carbon::createFromFormat('YmdHis', $timeString);
            }
            
            return Carbon::parse($timeString);
        } catch (\Exception $e) {
            Log::warning('Failed to parse M-PESA time', ['time' => $timeString]);
            return now();
        }
    }

    /**
     * Send payment confirmation notification
     */
    protected function sendPaymentConfirmation(\App\Models\Payment $payment)
    {
        try {
            $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);
            $paymentController->sendPaymentNotifications($payment);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentLink;
use App\Models\PaymentTransaction;
use App\Models\MpesaC2BTransaction;
use App\Services\PaymentGateways\MpesaGateway;
use App\Services\PaymentAllocationService;
use App\Services\MpesaSmartMatchingService;
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
    protected SMSService $smsService;
    protected EmailService $emailService;
    protected WhatsAppService $whatsappService;

    public function __construct(
        MpesaGateway $mpesaGateway,
        PaymentAllocationService $allocationService,
        MpesaSmartMatchingService $smartMatchingService,
        SMSService $smsService,
        EmailService $emailService,
        WhatsAppService $whatsappService
    ) {
        $this->mpesaGateway = $mpesaGateway;
        $this->allocationService = $allocationService;
        $this->smartMatchingService = $smartMatchingService;
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
            'send_channels' => 'nullable|array',
            'send_channels.*' => 'in:sms,email,whatsapp',
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

        try {
            $result = $this->mpesaGateway->initiateAdminPromptedPayment(
                studentId: $request->student_id,
                phoneNumber: $phoneNumber,
                amount: $request->amount,
                invoiceId: $request->invoice_id,
                adminId: Auth::id(),
                notes: $request->notes
            );

            if ($result['success']) {
                // Send notifications if channels are selected
                if ($request->filled('send_channels')) {
                    $this->sendPaymentNotifications(
                        $result['student'],
                        $request->amount,
                        $request->send_channels,
                        'STK Push payment request sent'
                    );
                }

                // Automatically redirect to waiting screen
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
                ? explode(',', $request->selected_invoices) 
                : [];

            // Get first invoice for the invoice_id field (backward compatibility)
            $primaryInvoiceId = !empty($invoiceIds) ? $invoiceIds[0] : null;

            $expiresAt = null;
            if ($request->filled('expires_in_days')) {
                $expiresAt = now()->addDays((int) $request->expires_in_days);
            }

            // Create description from invoices
            $description = 'School Fee Payment';
            if (!empty($invoiceIds)) {
                $invoices = Invoice::whereIn('id', $invoiceIds)->get();
                $invoiceNumbers = $invoices->pluck('invoice_number')->toArray();
                $description = 'Payment for ' . implode(', ', $invoiceNumbers);
            }

            $paymentLink = PaymentLink::create([
                'student_id' => $request->student_id,
                'invoice_id' => $primaryInvoiceId,
                'family_id' => $student->family_id,
                'amount' => $request->amount,
                'currency' => 'KES',
                'description' => $description,
                'expires_at' => $expiresAt,
                'max_uses' => $request->max_uses ?? 1,
                'created_by' => Auth::id(),
                'status' => 'active',
                'metadata' => [
                    'invoice_ids' => $invoiceIds,
                    'selected_parents' => $request->parents,
                ],
            ]);

            // Send payment link via selected channels to selected parents
            $this->sendPaymentLinkToParents(
                $student, 
                $paymentLink, 
                $request->send_channels,
                $request->parents
            );

            return redirect()
                ->route('finance.mpesa.link.show', $paymentLink->id)
                ->with('success', 'Payment link created and sent successfully!');
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
     * Show public payment page (for payment links)
     */
    public function showPaymentPage($identifier)
    {
        // Try to find by hashed_id or token
        $paymentLink = PaymentLink::where('hashed_id', $identifier)
            ->orWhere('token', $identifier)
            ->with(['student', 'invoice'])
            ->firstOrFail();

        if (!$paymentLink->isActive()) {
            return view('finance.mpesa.link-expired', compact('paymentLink'));
        }

        return view('finance.mpesa.payment-page', compact('paymentLink'));
    }

    /**
     * Process payment from link
     */
    public function processLinkPayment(Request $request, $identifier)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $paymentLink = PaymentLink::where('hashed_id', $identifier)
            ->orWhere('token', $identifier)
            ->firstOrFail();

        if (!$paymentLink->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment link is no longer active.',
            ], 400);
        }

        // Get payment amount (allow partial payments)
        $amount = $request->amount ?? $paymentLink->amount;
        
        // Validate amount doesn't exceed link amount
        if ($amount > $paymentLink->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed KES ' . number_format($paymentLink->amount, 2),
            ], 400);
        }

        // Validate phone number
        if (!MpesaGateway::isValidKenyanPhone($request->phone_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number. Please use a valid Kenyan mobile number.',
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

        // Refresh transaction from database to get latest status (webhook may have updated it)
        $transaction->refresh();

        // Check if already completed/failed/cancelled
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

        // Still processing - query M-PESA API
        try {
            $result = $this->mpesaGateway->queryStkPushStatus($transaction->transaction_id);

            Log::info('M-PESA Query Response', [
                'transaction_id' => $transaction->id,
                'result' => $result,
            ]);

            if ($result['success'] && isset($result['data'])) {
                $data = $result['data'];
                
                // Update transaction based on M-PESA response
                if (isset($data['ResultCode'])) {
                    if ($data['ResultCode'] == 0) {
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
                    } else {
                        // Failed
                        $transaction->update([
                            'status' => 'failed',
                            'failure_reason' => $data['ResultDesc'] ?? 'Payment failed',
                        ]);
                        
                        return response()->json([
                            'status' => 'failed',
                            'message' => 'Payment failed',
                            'failure_reason' => $transaction->failure_reason,
                        ]);
                    }
                }
            }

            // Still pending
            return response()->json([
                'status' => $transaction->status,
                'message' => 'Payment is being processed',
            ]);
        } catch (\Exception $e) {
            Log::error('Transaction status check failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

            // Allocate payment to invoices
            $this->allocatePaymentToInvoices($payment, $transaction);

            DB::commit();

            // Generate receipt PDF (outside transaction to avoid locking)
            try {
                $receiptService = app(\App\Services\ReceiptService::class);
                $pdfPath = $receiptService->generateReceipt($payment, ['save' => true]);
                Log::info('Receipt generated for M-PESA payment (query response)', [
                    'payment_id' => $payment->id,
                    'pdf_path' => $pdfPath,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate receipt for M-PESA payment (query response)', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send payment confirmation
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
     * Allocate payment to invoices
     */
    protected function allocatePaymentToInvoices(Payment $payment, PaymentTransaction $transaction)
    {
        $remainingAmount = $payment->amount;

        // If transaction has specific invoice, prioritize it
        if ($transaction->invoice_id) {
            $invoice = Invoice::find($transaction->invoice_id);
            if ($invoice && $invoice->balance > 0) {
                $allocationAmount = min($remainingAmount, $invoice->balance);
                
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                ]);

                $remainingAmount -= $allocationAmount;

                // Update invoice
                $invoice->amount_paid += $allocationAmount;
                if ($invoice->amount_paid >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                }
                $invoice->save();
            }
        }

        // If there's remaining amount, allocate to oldest unpaid invoices
        if ($remainingAmount > 0) {
            $unpaidInvoices = Invoice::where('student_id', $transaction->student_id)
                ->where('status', '!=', 'paid')
                ->where('balance', '>', 0)
                ->orderBy('due_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($unpaidInvoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                // Skip if already allocated in this payment
                if ($invoice->id == $transaction->invoice_id) {
                    continue;
                }

                $allocationAmount = min($remainingAmount, $invoice->balance);
                
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                ]);

                $remainingAmount -= $allocationAmount;

                // Update invoice
                $invoice->amount_paid += $allocationAmount;
                if ($invoice->amount_paid >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                }
                $invoice->save();
            }
        }

        // If still remaining (overpayment), create credit note or mark as advance payment
        if ($remainingAmount > 0.01) {
            // This could be handled by creating a credit note or marking as advance payment
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
                $whatsappPhone = !empty($parent->father_whatsapp) ? $parent->father_whatsapp 
                    : (!empty($parent->mother_whatsapp) ? $parent->mother_whatsapp 
                    : (!empty($parent->guardian_whatsapp) ? $parent->guardian_whatsapp 
                    : (!empty($parent->father_phone) ? $parent->father_phone 
                    : (!empty($parent->mother_phone) ? $parent->mother_phone 
                    : (!empty($parent->guardian_phone) ? $parent->guardian_phone : null)))));
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
        if (!$student->family) {
            return;
        }

        $family = $student->family;
        $studentName = $student->first_name . ' ' . $student->last_name;
        $amountFormatted = number_format($paymentLink->amount, 2);
        $linkUrl = route('payment-link.show', $paymentLink->hashed_id);

        // Prepare parent contacts
        $contacts = [];
        
        if (in_array('father', $parents)) {
            $parent = $student->parent;
            $whatsappPhone = !empty($parent->father_whatsapp) ? $parent->father_whatsapp 
                : (!empty($parent->father_phone) ? $parent->father_phone : null);
            
            $contacts[] = [
                'name' => $family->father_name ?? 'Father',
                'phone' => $whatsappPhone ?? $family->father_phone,
                'email' => $family->father_email,
            ];
        }
        
        if (in_array('mother', $parents)) {
            $parent = $student->parent;
            $whatsappPhone = !empty($parent->mother_whatsapp) ? $parent->mother_whatsapp 
                : (!empty($parent->mother_phone) ? $parent->mother_phone : null);
            
            $contacts[] = [
                'name' => $family->mother_name ?? 'Mother',
                'phone' => $whatsappPhone ?? $family->mother_phone,
                'email' => $family->mother_email,
            ];
        }
        
        if (in_array('primary', $parents)) {
            $contacts[] = [
                'name' => 'Parent',
                'phone' => $family->phone,
                'email' => $family->email,
            ];
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
        $student->load(['family', 'classroom']);
        
        return response()->json([
            'id' => $student->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'admission_number' => $student->admission_number,
            'classroom' => $student->classroom ? [
                'id' => $student->classroom->id,
                'name' => $student->classroom->name,
            ] : null,
            'family' => $student->family ? [
                'id' => $student->family->id,
                'phone' => $student->family->phone,
                'email' => $student->family->email,
                'father_name' => $student->family->father_name,
                'father_phone' => $student->family->father_phone,
                'father_email' => $student->family->father_email,
                'mother_name' => $student->family->mother_name,
                'mother_phone' => $student->family->mother_phone,
                'mother_email' => $student->family->mother_email,
            ] : null,
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
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => $invoice->total_amount,
                    'amount_paid' => $invoice->amount_paid,
                    'balance' => $invoice->balance,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'academic_year' => $invoice->academicYear?->name,
                    'term' => $invoice->term?->name,
                ];
            });

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

            // Check for duplicates
            $isDuplicate = $c2bTransaction->checkForDuplicate();
            
            if (!$isDuplicate) {
                // Attempt smart matching
                $this->smartMatchingService->matchTransaction($c2bTransaction);
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
     */
    public function c2bTransactionShow($id)
    {
        $transaction = MpesaC2BTransaction::with(['student', 'invoice', 'payment', 'processedBy'])
            ->findOrFail($id);

        // Get matching suggestions if not already matched
        if (empty($transaction->matching_suggestions)) {
            $this->smartMatchingService->matchTransaction($transaction);
            $transaction->refresh();
        }

        return view('finance.mpesa.c2b-allocate', compact('transaction'));
    }

    /**
     * Allocate C2B transaction to student/invoice
     */
    public function c2bAllocate(Request $request, $id)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mpesa',
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:1',
        ]);

        $transaction = MpesaC2BTransaction::findOrFail($id);

        try {
            DB::beginTransaction();

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

            // Allocate to invoices
            foreach ($request->allocations as $allocation) {
                $invoice = Invoice::findOrFail($allocation['invoice_id']);
                $allocationAmount = $allocation['amount'];

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $allocationAmount,
                ]);

                // Update invoice
                $invoice->amount_paid += $allocationAmount;
                if ($invoice->amount_paid >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                }
                $invoice->save();
            }

            // Update C2B transaction
            $transaction->allocate($student, null, $payment, $request->amount);

            DB::commit();

            Log::info('C2B transaction allocated', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'amount' => $request->amount,
            ]);

            return redirect()
                ->route('finance.mpesa.c2b.dashboard')
                ->with('success', 'Transaction allocated successfully! Receipt: ' . $payment->receipt_number);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('C2B allocation failed', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to allocate transaction: ' . $e->getMessage());
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
            $confirmationUrl = $request->input('confirmation_url') 
                ?? config('mpesa.confirmation_url')
                ?? route('payment.webhook.mpesa.c2b');
            
            $validationUrl = $request->input('validation_url') 
                ?? config('mpesa.validation_url')
                ?? route('payment.webhook.mpesa.c2b') . '/validation';
            
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
            $student = $payment->student;
            $parent = $student->family;

            if (!$parent) {
                return;
            }

            $commService = app(\App\Services\CommunicationService::class);

            $message = "Dear Parent,\n\n";
            $message .= "Payment of KES " . number_format($payment->amount, 2) . " ";
            $message .= "for {$student->first_name} {$student->last_name} has been received.\n\n";
            $message .= "M-PESA Ref: " . ($payment->mpesa_receipt_number ?? 'N/A') . "\n";
            $message .= "Receipt No: " . $payment->receipt_number . "\n";
            $message .= "Date: " . $payment->payment_date->format('d M Y H:i') . "\n\n";
            
            // Add balance information
            $balance = \App\Models\Invoice::where('student_id', $student->id)
                ->where('status', '!=', 'paid')
                ->sum('balance');
            
            if ($balance > 0) {
                $message .= "Outstanding Balance: KES " . number_format($balance, 2) . "\n";
            } else {
                $message .= "✅ All fees paid. Thank you!\n";
            }
            
            $message .= "\nView receipt: " . route('receipts.public', $payment->public_token);
            $message .= "\n\nThank you!";

            // Send SMS
            if ($parent->primary_phone) {
                $commService->sendSMS('parent', $parent->id, $parent->primary_phone, $message, 'Payment Confirmation');
            }

            // Send Email
            if ($parent->primary_email) {
                $htmlMessage = nl2br($message);
                $commService->sendEmail('parent', $parent->id, $parent->primary_email, 'Payment Confirmation - ' . $payment->receipt_number, $htmlMessage);
            }

            // Send WhatsApp if available
            try {
                $whatsappPhone = $parent->father_whatsapp ?? $parent->mother_whatsapp ?? $parent->primary_phone ?? null;
                if ($whatsappPhone) {
                    $commService->sendWhatsApp('parent', $parent->id, $whatsappPhone, $message, 'Payment Confirmation');
                }
            } catch (\Exception $e) {
                Log::warning('WhatsApp sending failed for payment confirmation', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


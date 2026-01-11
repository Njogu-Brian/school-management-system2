<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\MpesaGateway;
use App\Services\PaymentAllocationService;
use App\Services\SMSService;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MpesaPaymentController extends Controller
{
    protected MpesaGateway $mpesaGateway;
    protected PaymentAllocationService $allocationService;
    protected SMSService $smsService;
    protected EmailService $emailService;
    protected WhatsAppService $whatsappService;

    public function __construct(
        MpesaGateway $mpesaGateway,
        PaymentAllocationService $allocationService,
        SMSService $smsService,
        EmailService $emailService,
        WhatsAppService $whatsappService
    ) {
        $this->mpesaGateway = $mpesaGateway;
        $this->allocationService = $allocationService;
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

        return view('finance.mpesa.dashboard', compact('stats', 'recentTransactions', 'activeLinks'));
    }

    /**
     * Show form to initiate admin-prompted STK push
     */
    public function promptPaymentForm(Request $request)
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

        return view('finance.mpesa.prompt-payment', compact('student', 'invoice'));
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

        // Validate phone number
        if (!MpesaGateway::isValidKenyanPhone($request->phone_number)) {
            return back()->with('error', 'Invalid phone number. Please use a valid Kenyan mobile number.');
        }

        try {
            $result = $this->mpesaGateway->initiateAdminPromptedPayment(
                studentId: $request->student_id,
                phoneNumber: $request->phone_number,
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

                return redirect()
                    ->route('finance.mpesa.transaction.show', $result['transaction_id'])
                    ->with('success', 'Payment request sent successfully. Parent will receive STK push prompt on their phone.');
            }

            return back()->with('error', $result['message'] ?? 'Failed to initiate payment.');
        } catch (\Exception $e) {
            Log::error('Admin-prompted STK push failed', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'An error occurred: ' . $e->getMessage());
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

        // WhatsApp
        if (in_array('whatsapp', $channels) && $family->phone) {
            $whatsappMessage = "*M-PESA Payment Notification*\n\n";
            $whatsappMessage .= "Dear Parent,\n\n";
            $whatsappMessage .= "$message for *$studentName*\n";
            $whatsappMessage .= "Amount: *KES $amountFormatted*\n\n";
            $whatsappMessage .= "Thank you,\nRoyal Kings School";
            
            $this->whatsappService->sendMessage(
                $family->phone,
                $whatsappMessage
            );
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
            $contacts[] = [
                'name' => $family->father_name ?? 'Father',
                'phone' => $family->father_phone,
                'email' => $family->father_email,
            ];
        }
        
        if (in_array('mother', $parents)) {
            $contacts[] = [
                'name' => $family->mother_name ?? 'Mother',
                'phone' => $family->mother_phone,
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
}


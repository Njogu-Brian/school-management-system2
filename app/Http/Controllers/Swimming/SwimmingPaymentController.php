<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use App\Models\{Student, Payment, PaymentMethod, BankAccount, SwimmingWallet, PaymentLink, CommunicationTemplate};
use App\Services\SwimmingWalletService;
use App\Services\CommunicationService;
use App\Jobs\BulkSendSwimmingBalanceNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SwimmingPaymentController extends Controller
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Show payment creation form
     */
    public function create(Request $request)
    {
        $paymentMethods = PaymentMethod::active()->orderBy('display_order')->get();
        $bankAccounts = BankAccount::active()->get();
        
        $studentId = $request->query('student_id');
        $student = null;
        
        if ($studentId) {
            $student = Student::withAlumni()->find($studentId);
        }
        
        return view('swimming.payments.create', [
            'paymentMethods' => $paymentMethods,
            'bankAccounts' => $bankAccounts,
            'student' => $student,
        ]);
    }

    /**
     * Store swimming payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date' => 'required|date',
            'payer_name' => 'nullable|string|max:255',
            'payer_type' => 'nullable|in:parent,student,other',
            'transaction_code' => 'nullable|string|max:255',
            'narration' => 'nullable|string|max:500',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'share_with_siblings' => 'boolean',
            'sibling_allocations' => 'nullable|array',
            'sibling_allocations.*.student_id' => 'exists:students,id',
            'sibling_allocations.*.amount' => 'numeric|min:0.01',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $student = Student::withAlumni()->findOrFail($validated['student_id']);
                $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
                
                // Handle sibling sharing
                if (!empty($validated['share_with_siblings']) && !empty($validated['sibling_allocations'])) {
                    // Flatten the sibling allocations array (it comes as nested array)
                    $allocations = [];
                    foreach ($validated['sibling_allocations'] as $key => $allocation) {
                        if (is_array($allocation) && isset($allocation['student_id']) && isset($allocation['amount'])) {
                            $allocations[] = $allocation;
                        }
                    }
                    
                    if (empty($allocations)) {
                        return redirect()->back()
                            ->with('error', 'Please allocate amounts to siblings.')
                            ->withInput();
                    }
                    
                    $totalAmount = array_sum(array_column($allocations, 'amount'));
                    
                    if (abs($totalAmount - $validated['amount']) > 0.01) {
                        return redirect()->back()
                            ->with('error', 'Total sibling allocation amount (' . number_format($totalAmount, 2) . ') must equal payment amount (' . number_format($validated['amount'], 2) . ').')
                            ->withInput();
                    }
                    
                    // Create payments for each sibling
                    $createdPayments = [];
                    $transactionCode = $validated['transaction_code'] ?? 'SWIM-' . time();
                    
                    foreach ($allocations as $allocation) {
                        if (empty($allocation['amount']) || $allocation['amount'] <= 0) {
                            continue; // Skip zero allocations
                        }
                        
                        $sibling = Student::findOrFail($allocation['student_id']);
                        
                        $payment = Payment::create([
                            'student_id' => $sibling->id,
                            'family_id' => $sibling->family_id,
                            'amount' => $allocation['amount'],
                            'payment_method_id' => $paymentMethod->id,
                            'payment_method' => $paymentMethod->name,
                            'payment_date' => $validated['payment_date'],
                            'transaction_code' => $transactionCode,
                            'payer_name' => $validated['payer_name'] ?? $sibling->first_name . ' ' . $sibling->last_name,
                            'payer_type' => $validated['payer_type'] ?? 'parent',
                            'narration' => ($validated['narration'] ?? 'Swimming payment') . ' (Shared)',
                            'bank_account_id' => $validated['bank_account_id'] ?? null,
                        ]);
                        
                        // Credit swimming wallet
                        $this->walletService->creditFromTransaction(
                            $sibling,
                            $payment,
                            $allocation['amount'],
                            $validated['narration'] ?? "Swimming payment - {$paymentMethod->name}"
                        );
                        
                        $createdPayments[] = $payment;
                    }
                    
                    return redirect()->route('swimming.wallets.index')
                        ->with('success', 'Swimming payment created and allocated to ' . count($createdPayments) . ' student(s).');
                } else {
                    // Single student payment
                    $payment = Payment::create([
                        'student_id' => $student->id,
                        'family_id' => $student->family_id,
                        'amount' => $validated['amount'],
                        'payment_method_id' => $paymentMethod->id,
                        'payment_method' => $paymentMethod->name,
                        'payment_date' => $validated['payment_date'],
                        'transaction_code' => $validated['transaction_code'] ?? 'SWIM-' . time(),
                        'payer_name' => $validated['payer_name'] ?? $student->first_name . ' ' . $student->last_name,
                        'payer_type' => $validated['payer_type'] ?? 'parent',
                        'narration' => $validated['narration'] ?? 'Swimming payment',
                        'bank_account_id' => $validated['bank_account_id'] ?? null,
                    ]);
                    
                    // Credit swimming wallet
                    $this->walletService->creditFromTransaction(
                        $student,
                        $payment,
                        $validated['amount'],
                        $validated['narration'] ?? "Swimming payment - {$paymentMethod->name}"
                    );
                    
                    return redirect()->route('swimming.wallets.show', $student)
                        ->with('success', 'Swimming payment created and wallet credited successfully.');
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to create swimming payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get siblings for a student
     */
    public function getSiblings(Student $student)
    {
        $siblings = Student::where('family_id', $student->family_id)
            ->where('id', '!=', $student->id)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->select('id', 'first_name', 'last_name', 'admission_number')
            ->get();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'admission_number' => $student->admission_number,
                'swimming_balance' => \App\Models\SwimmingWallet::getOrCreateForStudent($student->id)->balance ?? 0,
            ],
            'siblings' => $siblings->map(function($sibling) {
                $wallet = \App\Models\SwimmingWallet::getOrCreateForStudent($sibling->id);
                return [
                    'id' => $sibling->id,
                    'first_name' => $sibling->first_name,
                    'last_name' => $sibling->last_name,
                    'admission_number' => $sibling->admission_number,
                    'swimming_balance' => $wallet->balance ?? 0,
                ];
            })
        ]);
    }

    /**
     * Send balance communication for a single student
     */
    public function sendBalanceCommunication(Request $request, Student $student)
    {
        $request->validate([
            'channels' => 'required|array',
            'channels.*' => 'in:sms,email,whatsapp',
            'amount' => 'nullable|numeric|min:0.01',
            'expiration_days' => 'nullable|integer|min:1|max:365',
        ]);

        try {
            $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
            $balance = (float) $wallet->balance;

            // Only send for negative balances
            if ($balance >= 0) {
                return redirect()->back()
                    ->with('error', 'Student has no outstanding balance. Balance: Ksh ' . number_format($balance, 2));
            }

            $linkAmount = $request->amount ?? abs($balance);
            $expirationDays = $request->expiration_days ?? 30;

            // Create payment link
            $paymentLink = $this->createSwimmingPaymentLink($student, $linkAmount, $expirationDays);

            // Send communications
            $sentChannels = $this->sendBalanceNotification(
                $student,
                $wallet,
                $paymentLink,
                $request->channels
            );

            if (empty($sentChannels)) {
                return redirect()->back()
                    ->with('warning', 'No communications sent. Please check parent contact information.');
            }

            return redirect()->back()
                ->with('success', 'Balance communication sent via: ' . implode(', ', $sentChannels));

        } catch (\Exception $e) {
            Log::error('Failed to send swimming balance communication', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to send communication: ' . $e->getMessage());
        }
    }

    /**
     * Bulk send balance communications
     */
    public function bulkSendBalanceCommunications(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'channels' => 'required|array',
            'channels.*' => 'in:sms,email,whatsapp',
            'amount' => 'nullable|numeric|min:0.01',
            'expiration_days' => 'nullable|integer|min:1|max:365',
        ]);

        try {
            // Filter students with negative balances
            $studentIds = [];
            foreach ($request->student_ids as $studentId) {
                $wallet = SwimmingWallet::getOrCreateForStudent($studentId);
                if ($wallet->balance < 0) {
                    $studentIds[] = $studentId;
                }
            }

            if (empty($studentIds)) {
                return redirect()->back()
                    ->with('error', 'No students with outstanding balances found.');
            }

            $trackingId = 'SWIM-BAL-' . Str::random(8);
            $linkAmount = $request->amount;
            $expirationDays = $request->expiration_days ?? 30;

            // Dispatch job
            BulkSendSwimmingBalanceNotifications::dispatch(
                $trackingId,
                $studentIds,
                $request->channels,
                Auth::id(),
                $linkAmount,
                $expirationDays
            );

            return redirect()->back()
                ->with('success', 'Bulk send job queued for ' . count($studentIds) . ' student(s). Tracking ID: ' . $trackingId)
                ->with('tracking_id', $trackingId);

        } catch (\Exception $e) {
            Log::error('Failed to queue bulk send swimming balance', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to queue bulk send: ' . $e->getMessage());
        }
    }

    /**
     * Get progress of bulk send operation
     */
    public function getBulkSendProgress(Request $request)
    {
        $request->validate([
            'tracking_id' => 'required|string',
        ]);

        $cacheKey = "bulk_send_swimming_balance_progress_{$request->tracking_id}";
        $progress = \Illuminate\Support\Facades\Cache::get($cacheKey, [
            'status' => 'not_found',
            'message' => 'Progress not found or expired',
        ]);

        return response()->json($progress);
    }

    /**
     * Create payment link for swimming balance
     */
    protected function createSwimmingPaymentLink(Student $student, float $amount, int $expirationDays = 30): PaymentLink
    {
        $expiresAt = now()->addDays($expirationDays);
        
        return PaymentLink::create([
            'student_id' => $student->id,
            'invoice_id' => null,
            'family_id' => $student->family_id,
            'amount' => $amount,
            'currency' => 'KES',
            'description' => 'Swimming balance payment',
            'account_reference' => 'SWIM-' . $student->admission_number,
            'expires_at' => $expiresAt,
            'max_uses' => 1,
            'created_by' => Auth::id(),
            'status' => 'active',
            'metadata' => [
                'is_swimming' => true,
                'swimming_balance' => $amount,
            ],
        ]);
    }

    /**
     * Send balance notification via specified channels
     */
    protected function sendBalanceNotification(
        Student $student,
        SwimmingWallet $wallet,
        PaymentLink $paymentLink,
        array $channels
    ): array {
        $balance = abs((float) $wallet->balance);
        $paymentLinkUrl = $paymentLink->getPaymentUrl();
        $parent = $student->parent ?? null;

        if (!$parent) {
            return [];
        }

        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? null;
        $greeting = $parentName ? "Dear {$parentName}" : "Dear Parent";
        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');

        $variables = [
            'parent_name' => $parentName ?? 'Parent',
            'greeting' => $greeting,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'admission_number' => $student->admission_number,
            'balance' => 'Ksh ' . number_format($balance, 2),
            'payment_link' => $paymentLinkUrl,
            'school_name' => $schoolName,
        ];

        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };

        $commService = app(CommunicationService::class);
        $sentChannels = [];

        // SMS
        if (in_array('sms', $channels)) {
            $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            if ($parentPhone) {
                try {
                    $smsTemplate = CommunicationTemplate::where('code', 'swimming_balance_sms')->first();
                    
                    if (!$smsTemplate) {
                        $smsTemplate = CommunicationTemplate::firstOrCreate(
                            ['code' => 'swimming_balance_sms'],
                            [
                                'title' => 'Swimming Balance SMS',
                                'type' => 'sms',
                                'subject' => null,
                                'content' => "{{greeting}},\n\n{{student_name}} ({{admission_number}}) has an outstanding swimming balance of {{balance}}.\n\nPay now: {{payment_link}}\n\nThank you.\n{{school_name}}",
                            ]
                        );
                    }

                    $smsMessage = $replacePlaceholders($smsTemplate->content, $variables);
                    $smsService = app(\App\Services\SMSService::class);
                    $financeSenderId = $smsService->getFinanceSenderId();
                    $commService->sendSMS('parent', $parent->id ?? null, $parentPhone, $smsMessage, $smsTemplate->subject ?? $smsTemplate->title, $financeSenderId);
                    $sentChannels[] = 'sms';
                } catch (\Exception $e) {
                    Log::error('Failed to send SMS for swimming balance', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Email
        if (in_array('email', $channels)) {
            $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
            if ($parentEmail) {
                try {
                    $emailTemplate = CommunicationTemplate::where('code', 'swimming_balance_email')->first();
                    
                    if (!$emailTemplate) {
                        $emailTemplate = CommunicationTemplate::firstOrCreate(
                            ['code' => 'swimming_balance_email'],
                            [
                                'title' => 'Swimming Balance Email',
                                'type' => 'email',
                                'subject' => 'Outstanding Swimming Balance – {{student_name}}',
                                'content' => "{{greeting}},\n\n{{student_name}} ({{admission_number}}) has an outstanding swimming balance of {{balance}}.\n\nPlease make payment using the link below:\n{{payment_link}}\n\nThank you for your cooperation.\n\nKind regards,\n{{school_name}} Finance Office",
                            ]
                        );
                    }

                    $emailSubject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                    $emailContent = $replacePlaceholders($emailTemplate->content, $variables);
                    $emailContent = nl2br($emailContent);
                    $commService->sendEmail('parent', $parent->id ?? null, $parentEmail, $emailSubject, $emailContent);
                    $sentChannels[] = 'email';
                } catch (\Exception $e) {
                    Log::error('Failed to send email for swimming balance', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // WhatsApp
        if (in_array('whatsapp', $channels)) {
            $whatsappPhone = $parent->father_whatsapp ?? $parent->mother_whatsapp ?? $parent->guardian_whatsapp
                ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            
            if ($whatsappPhone) {
                try {
                    $whatsappTemplate = CommunicationTemplate::where('code', 'swimming_balance_whatsapp')->first();
                    
                    if (!$whatsappTemplate) {
                        $whatsappTemplate = CommunicationTemplate::firstOrCreate(
                            ['code' => 'swimming_balance_whatsapp'],
                            [
                                'title' => 'Swimming Balance WhatsApp',
                                'type' => 'whatsapp',
                                'subject' => null,
                                'content' => "{{greeting}},\n\n{{student_name}} ({{admission_number}}) has an outstanding swimming balance of *{{balance}}*.\n\nPay now: {{payment_link}}\n\nThank you.\n{{school_name}}",
                            ]
                        );
                    }

                    $whatsappMessage = $replacePlaceholders($whatsappTemplate->content, $variables);
                    $whatsappService = app(\App\Services\WhatsAppService::class);
                    $whatsappService->sendMessage($whatsappPhone, $whatsappMessage);
                    $sentChannels[] = 'whatsapp';
                } catch (\Exception $e) {
                    Log::error('Failed to send WhatsApp for swimming balance', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $sentChannels;
    }
}

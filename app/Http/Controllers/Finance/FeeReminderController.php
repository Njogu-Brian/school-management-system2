<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeReminder;
use App\Models\Invoice;
use App\Models\ScheduledFeeCommunication;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use App\Services\FeeReminderAutomationSettings;
use App\Services\SMSService;
use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeeReminderController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display fee reminders and scheduled communications (unified at /finance/fee-reminders)
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'sent');

        // Sent reminders
        $remindersQuery = FeeReminder::with(['student', 'invoice']);
        if ($request->filled('status')) {
            $remindersQuery->where('status', $request->status);
        }
        if ($request->filled('student_id')) {
            $remindersQuery->where('student_id', $request->student_id);
        }
        $reminders = $remindersQuery->latest()->paginate(20)->withQueryString();

        // Scheduled communications
        $scheduledQuery = ScheduledFeeCommunication::with(['student', 'template', 'createdBy'])->latest();
        if ($request->filled('status')) {
            $scheduledQuery->where('status', $request->status);
        }
        $scheduled = $scheduledQuery->paginate(20)->withQueryString();

        return view('finance.fee_reminders.index', compact('reminders', 'scheduled', 'tab'));
    }

    /**
     * Redirect to schedule form (unified send/schedule flow)
     */
    public function create()
    {
        return redirect()->route('finance.fee-reminders.schedule.create');
    }

    /**
     * Store manual reminder
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'channel' => 'required|in:email,sms,both',
            'days_before_due' => 'required|integer|min:0|max:365',
            'due_date' => 'required|date',
            'message' => 'nullable|string',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        
        // Calculate outstanding amount
        $outstanding = $this->calculateOutstanding($student, $validated['invoice_id'] ?? null);

        $reminder = FeeReminder::create([
            'student_id' => $validated['student_id'],
            'invoice_id' => $validated['invoice_id'] ?? null,
            'fee_reminder_type' => 'invoice',
            'channel' => $validated['channel'],
            'outstanding_amount' => $outstanding,
            'due_date' => $validated['due_date'],
            'days_before_due' => $validated['days_before_due'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        // Send immediately if due date is today or past
        if (Carbon::parse($validated['due_date'])->lte(now())) {
            $this->sendReminder($reminder);
        }

        return redirect()->route('finance.fee-reminders.index')
            ->with('success', 'Fee reminder created successfully.');
    }

    /**
     * Send reminder manually
     */
    public function send(FeeReminder $feeReminder)
    {
        try {
            $this->sendReminder($feeReminder);
            return back()->with('success', 'Reminder sent successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send reminder: ' . $e->getMessage());
        }
    }

    /**
     * Send reminder
     */
    protected function sendReminder(FeeReminder $reminder)
    {
        $student = $reminder->student;
        if (!$student) {
            return;
        }

        $student->loadMissing('category');
        if ($student->category && strtolower($student->category->name) === 'staff') {
            $reminder->update([
                'status' => 'failed',
                'error_message' => 'Skipped: student is in staff category (fee reminders are not sent to staff children).',
            ]);
            return;
        }

        $parent = $student->parent ?? null;
        $variables = $this->buildReminderVariables($reminder, $student, $parent);

        $replacePlaceholders = function ($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', (string) $value, $text);
            }
            return $text;
        };

        $channels = $this->channelsListForReminder($reminder);

        foreach ($channels as $channel) {
            if ($channel === 'email') {
                $emails = $parent ? $parent->schoolNotificationEmails() : [];
                $emailTemplate = $this->communicationTemplateForReminder($reminder, 'email');
                foreach ($emails as $email) {
                    try {
                        if ($emailTemplate && !$reminder->message) {
                            $subject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                            $message = $replacePlaceholders($emailTemplate->content, $variables);
                        } else {
                            $subject = 'Fee Payment Reminder';
                            $message = $reminder->message ?? $this->generateDefaultMessage($reminder);
                            $message = $replacePlaceholders($message, $variables);
                        }
                        Mail::to($email)->send(new GenericMail($subject, $message));
                    } catch (\Exception $e) {
                        $reminder->update([
                            'status' => 'failed',
                            'error_message' => 'Email failed: ' . $e->getMessage(),
                        ]);
                        return;
                    }
                }
                continue;
            }

            if ($channel === 'sms') {
                $phones = $parent ? $parent->schoolNotificationSmsPhones() : [];
                if (empty($phones) && $student->phone_number) {
                    $phones = [$student->phone_number];
                }
                $smsTemplate = $this->communicationTemplateForReminder($reminder, 'sms');
                foreach ($phones as $phone) {
                    try {
                        if ($smsTemplate && !$reminder->message) {
                            $message = $replacePlaceholders($smsTemplate->content, $variables);
                        } else {
                            $message = $reminder->message ?? $this->generateDefaultMessage($reminder);
                            $message = $replacePlaceholders($message, $variables);
                        }
                        $this->smsService->sendSMS($phone, $message, $this->smsService->getFinanceSenderId());
                    } catch (\Exception $e) {
                        $reminder->update([
                            'status' => 'failed',
                            'error_message' => 'SMS failed: ' . $e->getMessage(),
                        ]);
                        return;
                    }
                }
                continue;
            }

            if ($channel === 'whatsapp') {
                $whatsappPhones = $parent ? $parent->schoolNotificationWhatsAppNumbers() : [];
                if (empty($whatsappPhones) && !empty($student->phone_number)) {
                    $whatsappPhones = [$student->phone_number];
                }
                $waTemplate = $this->communicationTemplateForReminder($reminder, 'whatsapp');
                $whatsappService = app(\App\Services\WhatsAppService::class);
                foreach ($whatsappPhones as $whatsappPhone) {
                    try {
                        if ($waTemplate && !$reminder->message) {
                            $message = $replacePlaceholders($waTemplate->content, $variables);
                        } else {
                            $message = $reminder->message ?? $this->generateDefaultMessage($reminder);
                            $message = $replacePlaceholders($message, $variables);
                        }
                        $whatsappService->sendMessage($whatsappPhone, $message);
                    } catch (\Exception $e) {
                        \Log::warning('WhatsApp reminder failed', [
                            'reminder_id' => $reminder->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $reminder->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function channelsListForReminder(FeeReminder $reminder): array
    {
        if (is_array($reminder->channels) && count($reminder->channels) > 0) {
            return array_values(array_unique(array_map('strtolower', $reminder->channels)));
        }

        return match ($reminder->channel) {
            'email' => ['email'],
            'sms' => ['sms'],
            'whatsapp' => ['whatsapp'],
            'both' => ['email', 'sms', 'whatsapp'],
            default => ['email', 'sms'],
        };
    }

    protected function communicationTemplateForReminder(FeeReminder $reminder, string $channelType): ?CommunicationTemplate
    {
        if ($reminder->message) {
            return null;
        }

        $reason = (string) ($reminder->reason_code ?: 'pending');
        $reason = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', $reason));

        if (($reminder->fee_reminder_type ?? 'invoice') === 'clearance') {
            $specific = CommunicationTemplate::where('code', "fee_clearance_reminder_{$reason}_{$channelType}")->first();
            if ($specific) {
                return $specific;
            }
            $fallback = CommunicationTemplate::where('code', "fee_clearance_reminder_pending_{$channelType}")->first();
            if ($fallback) {
                return $fallback;
            }
        }

        $code = match ($channelType) {
            'sms' => 'finance_fee_reminder_sms',
            'email' => 'finance_fee_plan_email',
            'whatsapp' => 'finance_fee_reminder_whatsapp',
            default => null,
        };
        if (!$code) {
            return null;
        }

        $tpl = CommunicationTemplate::where('code', $code)->first();
        if ($tpl) {
            return $tpl;
        }

        return match ($channelType) {
            'sms' => CommunicationTemplate::firstOrCreate(
                ['code' => 'finance_fee_reminder_sms'],
                [
                    'title' => 'Fee Reminder (SMS)',
                    'type' => 'sms',
                    'subject' => null,
                    'content' => "Dear {{parent_name}},\n\nFriendly reminder: there is an outstanding fee balance for {{student_name}} for {{term_name}}, {{academic_year}}.\nPlease review details here:\n{{finance_portal_link}}\n\nThank you for your cooperation.\n{{school_name}}",
                ]
            ),
            'email' => CommunicationTemplate::firstOrCreate(
                ['code' => 'finance_fee_plan_email'],
                [
                    'title' => 'Fee Payment Plan (Email)',
                    'type' => 'email',
                    'subject' => 'Fee Payment Update – {{student_name}}',
                    'content' => "Dear {{parent_name}},\n\nSchool fees for {{student_name}} remain pending for {{term_name}}, {{academic_year}}.\nIf you are on a payment plan or need assistance, kindly reach out.\n\nView the full statement here:\n{{finance_portal_link}}\n\nWe appreciate your continued partnership.\n\nWarm regards,\n{{school_name}} Accounts Office",
                ]
            ),
            'whatsapp' => CommunicationTemplate::firstOrCreate(
                ['code' => 'finance_fee_reminder_whatsapp'],
                [
                    'title' => 'Fee Reminder (WhatsApp)',
                    'type' => 'whatsapp',
                    'subject' => null,
                    'content' => "Dear {{parent_name}},\n\nFriendly reminder: outstanding fees for {{student_name}} ({{term_name}}, {{academic_year}}).\nDetails: {{finance_portal_link}}\n\n{{school_name}}",
                ]
            ),
            default => null,
        };
    }

    protected function buildReminderVariables(FeeReminder $reminder, Student $student, $parent): array
    {
        $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
        $parentName = $parent ? ($parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent') : 'Parent';
        $currentTerm = Term::where('is_current', true)->first();
        $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $financePortalLink = url('/finance/student-statements/' . $student->id);
        $payLink = null;
        if ($student->family_id) {
            $payLink = \App\Models\PaymentLink::getOrCreateFamilyLink((int) $student->family_id, auth()->id(), 'fee_reminder')->getPaymentUrl();
        }

        $variables = [
            'parent_name' => $parentName,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'term_name' => $currentTerm->name ?? 'Current Term',
            'academic_year' => $currentYear->year ?? date('Y'),
            'finance_portal_link' => $financePortalLink,
            'school_name' => $schoolName,
            'pay_link' => $payLink ?? '',
        ];

        if ($reminder->payment_plan_installment_id) {
            $installment = $reminder->paymentPlanInstallment;
            $plan = $reminder->paymentPlan;
            if ($installment && $plan) {
                $remainingBalance = $plan->total_amount - $plan->installments()->sum('paid_amount');
                $variables['installment_amount'] = number_format($installment->amount, 2);
                $variables['installment_number'] = (string) $installment->installment_number;
                $variables['due_date'] = $installment->due_date->format('F d, Y');
                $variables['remaining_balance'] = number_format($remainingBalance, 2);
                $variables['payment_plan_link'] = url('/payment-plan/' . $plan->hashed_id);
                if ($student->family_id) {
                    $variables['pay_link'] = \App\Models\PaymentLink::getOrCreateFamilyLink((int) $student->family_id, auth()->id(), 'payment_plan_reminder')->getPaymentUrl();
                }
            }
        }

        if (($reminder->fee_reminder_type ?? '') === 'clearance' && $reminder->term_id) {
            $term = Term::find($reminder->term_id);
            if ($term) {
                $variables['term_name'] = $term->name;
                if ($term->academicYear) {
                    $variables['academic_year'] = (string) $term->academicYear->year;
                }
            }
            $variables['fee_clearance_deadline'] = Carbon::parse($reminder->due_date)->format('d M Y');
            $variables['fee_clearance_reason'] = str_replace('_', ' ', (string) ($reminder->reason_code ?? ''));
            $variables['outstanding_amount'] = number_format((float) $reminder->outstanding_amount, 2);
        }

        return $variables;
    }

    /**
     * Generate default reminder message
     */
    protected function generateDefaultMessage(FeeReminder $reminder)
    {
        $student = $reminder->student;
        $amount = number_format($reminder->outstanding_amount, 2);
        $dueDate = Carbon::parse($reminder->due_date)->format('F d, Y');

        // If this is an installment reminder, include installment-specific details
        if ($reminder->payment_plan_installment_id) {
            $installment = $reminder->paymentPlanInstallment;
            $plan = $reminder->paymentPlan;
            $remainingBalance = $plan->total_amount - $plan->installments()->sum('paid_amount');
            $remainingBalanceFormatted = number_format($remainingBalance, 2);
            
            $message = "Dear Parent/Guardian,\n\n" .
                      "This is a reminder that installment #{$installment->installment_number} " .
                      "for {$student->first_name} {$student->last_name} " .
                      "amounting to KES {$amount} is due on {$dueDate}.\n\n";
            
            if ($remainingBalance > 0) {
                $message .= "Remaining balance on payment plan: KES {$remainingBalanceFormatted}.\n\n";
            }
            
            $message .= "Please make payment at your earliest convenience.\n\n" .
                       "Thank you.";
            
            return $message;
        }

        // Default invoice reminder
        return "Dear Parent/Guardian,\n\n" .
               "This is a reminder that your child {$student->first_name} {$student->last_name} " .
               "has an outstanding fee balance of KES {$amount} due on {$dueDate}.\n\n" .
               "Please make payment at your earliest convenience.\n\n" .
               "Thank you.";
    }

    /**
     * Calculate outstanding amount (including balance brought forward)
     */
    protected function calculateOutstanding(Student $student, ?int $invoiceId = null)
    {
        if ($invoiceId) {
            // For specific invoice, return invoice balance (balance brought forward is already in invoice if applicable)
            $invoice = Invoice::findOrFail($invoiceId);
            $invoice->recalculate();
            return max(0, $invoice->balance);
        }

        // Total outstanding including balance brought forward; all invoices (due or not yet due).
        return \App\Services\StudentBalanceService::getTotalOutstandingBalance($student, false);
    }

    /**
     * Automated reminder job - send reminders for due fees, installments, and fee clearance deadlines.
     */
    public function sendAutomatedReminders()
    {
        $cfg = FeeReminderAutomationSettings::load();
        // Scheduled job respects the master switch; manual run from Finance UI still works when disabled (for testing).
        if (!$cfg->enabled && app()->runningInConsole()) {
            return;
        }

        $daysBeforeDue = $cfg->daysBeforeDue;
        $daysAfterOverdue = $cfg->daysAfterOverdue;

        // Process invoice-based reminders
        foreach ($daysBeforeDue as $days) {
            $dueDate = now()->addDays($days)->format('Y-m-d');

            $invoices = Invoice::where('status', '!=', 'reversed')
                ->whereHas('student', function ($q) {
                    $q->whereNotNull('parent_id')
                        ->whereDoesntHave('category', function ($q2) {
                            $q2->whereRaw('LOWER(name) = ?', ['staff']);
                        });
                })
                ->with(['student.parent', 'payments'])
                ->get()
                ->filter(function ($invoice) use ($dueDate) {
                    $invoiceDueDate = $this->getInvoiceDueDate($invoice);
                    return $invoiceDueDate === $dueDate && $this->hasOutstanding($invoice);
                });

            foreach ($invoices as $invoice) {
                $student = $invoice->student;
                $outstanding = $this->calculateOutstanding($student, $invoice->id);

                $existing = FeeReminder::where('student_id', $student->id)
                    ->where('fee_reminder_type', 'invoice')
                    ->where('invoice_id', $invoice->id)
                    ->where('days_before_due', $days)
                    ->where('reminder_rule', 'before_due')
                    ->where('status', 'sent')
                    ->exists();

                if (!$existing && $outstanding > 0) {
                    $reminder = FeeReminder::create([
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'fee_reminder_type' => 'invoice',
                        'channel' => 'both',
                        'channels' => $cfg->channelsBeforeDue,
                        'outstanding_amount' => $outstanding,
                        'due_date' => $dueDate,
                        'days_before_due' => $days,
                        'reminder_rule' => 'before_due',
                        'status' => 'pending',
                    ]);

                    $this->sendReminder($reminder);
                }
            }
        }

        foreach ($daysBeforeDue as $days) {
            $dueDate = now()->addDays($days)->format('Y-m-d');

            $installments = \App\Models\FeePaymentPlanInstallment::where('due_date', $dueDate)
                ->whereIn('status', ['pending', 'partial'])
                ->whereHas('paymentPlan.student', function ($q) {
                    $q->whereNotNull('parent_id')
                        ->whereDoesntHave('category', function ($q2) {
                            $q2->whereRaw('LOWER(name) = ?', ['staff']);
                        });
                })
                ->with(['paymentPlan.student.parent', 'paymentPlan'])
                ->get();

            foreach ($installments as $installment) {
                $plan = $installment->paymentPlan;
                $student = $plan->student;
                $outstanding = $installment->amount - $installment->paid_amount;

                $existing = FeeReminder::where('student_id', $student->id)
                    ->where('fee_reminder_type', 'installment')
                    ->where('payment_plan_installment_id', $installment->id)
                    ->where('days_before_due', $days)
                    ->where('reminder_rule', 'before_due')
                    ->where('status', 'sent')
                    ->exists();

                if (!$existing && $outstanding > 0) {
                    $reminder = FeeReminder::create([
                        'student_id' => $student->id,
                        'payment_plan_id' => $plan->id,
                        'payment_plan_installment_id' => $installment->id,
                        'fee_reminder_type' => 'installment',
                        'channel' => 'both',
                        'channels' => $cfg->channelsBeforeDue,
                        'outstanding_amount' => $outstanding,
                        'due_date' => $dueDate,
                        'days_before_due' => $days,
                        'reminder_rule' => 'before_due',
                        'status' => 'pending',
                    ]);

                    $this->sendReminder($reminder);
                }
            }
        }

        $today = now()->format('Y-m-d');
        $installmentsDueToday = \App\Models\FeePaymentPlanInstallment::where('due_date', $today)
            ->whereIn('status', ['pending', 'partial'])
            ->whereHas('paymentPlan.student', function ($q) {
                $q->whereNotNull('parent_id')
                    ->whereDoesntHave('category', function ($q2) {
                        $q2->whereRaw('LOWER(name) = ?', ['staff']);
                    });
            })
            ->with(['paymentPlan.student.parent', 'paymentPlan'])
            ->get();

        foreach ($installmentsDueToday as $installment) {
            $plan = $installment->paymentPlan;
            $student = $plan->student;
            $outstanding = $installment->amount - $installment->paid_amount;

            $existing = FeeReminder::where('student_id', $student->id)
                ->where('fee_reminder_type', 'installment')
                ->where('payment_plan_installment_id', $installment->id)
                ->where('reminder_rule', 'on_due')
                ->where('status', 'sent')
                ->exists();

            if (!$existing && $outstanding > 0) {
                $reminder = FeeReminder::create([
                    'student_id' => $student->id,
                    'payment_plan_id' => $plan->id,
                    'payment_plan_installment_id' => $installment->id,
                    'fee_reminder_type' => 'installment',
                    'channel' => 'both',
                    'channels' => $cfg->channelsOnDue,
                    'outstanding_amount' => $outstanding,
                    'due_date' => $today,
                    'days_before_due' => 0,
                    'reminder_rule' => 'on_due',
                    'status' => 'pending',
                ]);

                $this->sendReminder($reminder);
            }
        }

        foreach ($daysAfterOverdue as $days) {
            $overdueDate = now()->subDays($days)->format('Y-m-d');

            $overdueInstallments = \App\Models\FeePaymentPlanInstallment::where('due_date', $overdueDate)
                ->whereIn('status', ['overdue', 'partial'])
                ->whereHas('paymentPlan.student', function ($q) {
                    $q->whereNotNull('parent_id')
                        ->whereDoesntHave('category', function ($q2) {
                            $q2->whereRaw('LOWER(name) = ?', ['staff']);
                        });
                })
                ->with(['paymentPlan.student.parent', 'paymentPlan'])
                ->get()
                ->filter(function ($installment) {
                    return ($installment->amount - $installment->paid_amount) > 0;
                });

            foreach ($overdueInstallments as $installment) {
                $plan = $installment->paymentPlan;
                $student = $plan->student;
                $outstanding = $installment->amount - $installment->paid_amount;

                $existing = FeeReminder::where('student_id', $student->id)
                    ->where('fee_reminder_type', 'installment')
                    ->where('payment_plan_installment_id', $installment->id)
                    ->where('days_before_due', $days)
                    ->where('reminder_rule', 'after_overdue')
                    ->where('status', 'sent')
                    ->exists();

                if (!$existing && $outstanding > 0) {
                    $reminder = FeeReminder::create([
                        'student_id' => $student->id,
                        'payment_plan_id' => $plan->id,
                        'payment_plan_installment_id' => $installment->id,
                        'fee_reminder_type' => 'installment',
                        'channel' => 'both',
                        'channels' => $cfg->channelsAfterOverdue,
                        'outstanding_amount' => $outstanding,
                        'due_date' => $installment->due_date->format('Y-m-d'),
                        'days_before_due' => $days,
                        'reminder_rule' => 'after_overdue',
                        'status' => 'pending',
                    ]);

                    $this->sendReminder($reminder);
                }
            }
        }

        // Fee clearance final deadline (term threshold) — uses editable per-reason templates when fee_reminder_type = clearance
        if ($cfg->clearanceEnabled) {
            foreach ($cfg->clearanceDaysBefore as $days) {
                $deadlineOn = now()->addDays($days)->toDateString();
                $this->sendClearanceDeadlineReminders($deadlineOn, 'before_due', $days, $cfg->clearanceChannelsBefore);
            }

            $this->sendClearanceDeadlineReminders(now()->toDateString(), 'on_due', 0, $cfg->clearanceChannelsOn);

            foreach ($cfg->clearanceDaysAfter as $days) {
                $deadlineWas = now()->subDays($days)->toDateString();
                $this->sendClearanceDeadlineReminders($deadlineWas, 'after_overdue', $days, $cfg->clearanceChannelsAfter);
            }
        }

        if (!app()->runningInConsole()) {
            return redirect()->route('finance.fee-reminders.index')
                ->with('success', 'Automated reminders processed. Parents are notified according to your automation settings.');
        }
    }

    /**
     * @param  list<string>  $channelList
     */
    protected function sendClearanceDeadlineReminders(string $deadlineDate, string $rule, int $daysParam, array $channelList): void
    {
        $snapshots = StudentTermFeeClearance::query()
            ->where('status', 'pending')
            ->whereDate('final_clearance_deadline', $deadlineDate)
            ->whereHas('student', function ($q) {
                $q->where('archive', 0)
                    ->where('is_alumni', false)
                    ->whereNotNull('parent_id')
                    ->whereDoesntHave('category', function ($q2) {
                        $q2->whereRaw('LOWER(name) = ?', ['staff']);
                    });
            })
            ->with(['student.parent', 'term.academicYear'])
            ->get();

        foreach ($snapshots as $snap) {
            $student = $snap->student;
            if (!$student) {
                continue;
            }
            $balance = (float) ($snap->meta['balance'] ?? 0);
            if ($balance <= 0) {
                continue;
            }

            $existing = FeeReminder::where('student_id', $student->id)
                ->where('fee_reminder_type', 'clearance')
                ->where('term_id', $snap->term_id)
                ->where('days_before_due', $daysParam)
                ->where('reminder_rule', $rule)
                ->where('status', 'sent')
                ->exists();

            if ($existing) {
                continue;
            }

            $reminder = FeeReminder::create([
                'student_id' => $student->id,
                'term_id' => $snap->term_id,
                'fee_reminder_type' => 'clearance',
                'reason_code' => $snap->reason_code,
                'channel' => 'both',
                'channels' => $channelList,
                'outstanding_amount' => $balance,
                'due_date' => $snap->final_clearance_deadline,
                'days_before_due' => $daysParam,
                'reminder_rule' => $rule,
                'status' => 'pending',
            ]);

            $this->sendReminder($reminder);
        }
    }

    protected function getInvoiceDueDate(Invoice $invoice)
    {
        if ($invoice->due_date) {
            return $invoice->due_date->format('Y-m-d');
        }
        $term = $invoice->term_id ? Term::find($invoice->term_id) : \App\Models\Term::where('name', $invoice->term)->first();
        if ($term && $term->end_date) {
            return $term->end_date->format('Y-m-d');
        }
        return $invoice->created_at->addDays(30)->format('Y-m-d');
    }

    protected function hasOutstanding(Invoice $invoice)
    {
        $paid = $invoice->payments()->sum('amount');
        return ($invoice->total - $paid) > 0;
    }
}

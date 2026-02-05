<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeReminder;
use App\Models\Invoice;
use App\Models\Student;
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
     * Display fee reminders
     */
    public function index(Request $request)
    {
        $query = FeeReminder::with(['student', 'invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $reminders = $query->latest()->paginate(20)->withQueryString();

        return view('finance.fee_reminders.index', compact('reminders'));
    }

    /**
     * Show form to create manual reminder
     */
    public function create()
    {
        $students = Student::with('classroom')->orderBy('first_name')->get();
        return view('finance.fee_reminders.create', compact('students'));
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
        $parent = $student->parent ?? null;

        // Use templates from CommunicationTemplateSeeder if no custom message
        $smsTemplate = null;
        $emailTemplate = null;
        
        if (!$reminder->message) {
            // Use finance_fee_reminder_sms for SMS
            $smsTemplate = \App\Models\CommunicationTemplate::where('code', 'finance_fee_reminder_sms')->first();
            
            // Use finance_fee_plan_email for Email
            $emailTemplate = \App\Models\CommunicationTemplate::where('code', 'finance_fee_plan_email')->first();
            
            // Fallback: create templates if seeder hasn't run yet
            if (!$smsTemplate) {
                $smsTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                    ['code' => 'finance_fee_reminder_sms'],
                    [
                        'title' => 'Fee Reminder (SMS)',
                        'type' => 'sms',
                        'subject' => null,
                        'content' => "Dear {{parent_name}},\n\nFriendly reminder: there is an outstanding fee balance for {{student_name}} for {{term_name}}, {{academic_year}}.\nPlease review details here:\n{{finance_portal_link}}\n\nThank you for your cooperation.\n{{school_name}}",
                    ]
                );
            }
            
            if (!$emailTemplate) {
                $emailTemplate = \App\Models\CommunicationTemplate::firstOrCreate(
                    ['code' => 'finance_fee_plan_email'],
                    [
                        'title' => 'Fee Payment Plan (Email)',
                        'type' => 'email',
                        'subject' => 'Fee Payment Update – {{student_name}}',
                        'content' => "Dear {{parent_name}},\n\nSchool fees for {{student_name}} remain pending for {{term_name}}, {{academic_year}}.\nIf you are on a payment plan or need assistance, kindly reach out.\n\nView the full statement here:\n{{finance_portal_link}}\n\nWe appreciate your continued partnership.\n\nWarm regards,\n{{school_name}} Accounts Office",
                    ]
                );
            }
        }

        // Prepare template variables
        $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
        $parentName = $parent ? ($parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent') : 'Parent';
        $currentTerm = \App\Models\Term::where('is_current', true)->first();
        $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $financePortalLink = url('/finance/student-statements/' . $student->id);
        
        $variables = [
            'parent_name' => $parentName,
            'student_name' => $student->full_name ?? $student->first_name . ' ' . $student->last_name,
            'term_name' => $currentTerm->name ?? 'Current Term',
            'academic_year' => $currentYear->year ?? date('Y'),
            'finance_portal_link' => $financePortalLink,
            'school_name' => $schoolName,
        ];

        // Add installment-specific variables if this is an installment reminder
        if ($reminder->payment_plan_installment_id) {
            $installment = $reminder->paymentPlanInstallment;
            $plan = $reminder->paymentPlan;
            $remainingBalance = $plan->total_amount - $plan->installments()->sum('paid_amount');
            
            $variables['installment_amount'] = number_format($installment->amount, 2);
            $variables['installment_number'] = $installment->installment_number;
            $variables['due_date'] = $installment->due_date->format('F d, Y');
            $variables['remaining_balance'] = number_format($remainingBalance, 2);
            $variables['payment_plan_link'] = url('/payment-plans/' . $plan->hashed_id);
        }
        
        // Replace placeholders
        $replacePlaceholders = function($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };

        if ($reminder->channel === 'email' || $reminder->channel === 'both') {
            $email = null;
            if ($parent) {
                // Never send fee-related communications to guardian; guardians are reached via manual number entry only
            $email = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? null;
            }
            
            if ($email) {
                try {
                    if ($emailTemplate && !$reminder->message) {
                        $subject = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
                        $message = $replacePlaceholders($emailTemplate->content, $variables);
                    } else {
                        $subject = 'Fee Payment Reminder';
                        $message = $reminder->message ?? $this->generateDefaultMessage($reminder);
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
        }

        if ($reminder->channel === 'sms' || $reminder->channel === 'both') {
            $phone = null;
            if ($parent) {
                // Never send fee-related communications to guardian; guardians are reached via manual number entry only
            $phone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? null;
            }
            $phone = $phone ?? $student->phone_number ?? null;
            
            if ($phone) {
                try {
                    if ($smsTemplate && !$reminder->message) {
                        $message = $replacePlaceholders($smsTemplate->content, $variables);
                    } else {
                        $message = $reminder->message ?? $this->generateDefaultMessage($reminder);
                    }
                    
                    $this->smsService->sendSMS($phone, $message);
                } catch (\Exception $e) {
                    $reminder->update([
                        'status' => 'failed',
                        'error_message' => 'SMS failed: ' . $e->getMessage(),
                    ]);
                    return;
                }
            }
        }

        // Handle WhatsApp channel - prioritize WhatsApp fields, fallback to father/mother phone
        if ($reminder->channel === 'whatsapp' || $reminder->channel === 'both') {
            $whatsappPhone = null;
            if ($parent) {
                // Never send fee-related communications to guardian; guardians are reached via manual number entry only
            $whatsappPhone = !empty($parent->father_whatsapp) ? $parent->father_whatsapp 
                    : (!empty($parent->mother_whatsapp) ? $parent->mother_whatsapp 
                    : (!empty($parent->father_phone) ? $parent->father_phone 
                    : (!empty($parent->mother_phone) ? $parent->mother_phone : null)));
            }
            $whatsappPhone = $whatsappPhone ?? (!empty($student->phone_number) ? $student->phone_number : null);
            
            if ($whatsappPhone) {
                try {
                    $whatsappService = app(\App\Services\WhatsAppService::class);
                    
                    if ($reminder->message) {
                        $message = $reminder->message;
                    } else {
                        $message = $this->generateDefaultMessage($reminder);
                    }
                    
                    // Replace placeholders for WhatsApp
                    $message = $replacePlaceholders($message, $variables);
                    
                    $whatsappService->sendMessage($whatsappPhone, $message);
                } catch (\Exception $e) {
                    // Don't fail the entire reminder if WhatsApp fails
                    \Log::warning('WhatsApp reminder failed', [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $reminder->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
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

        // Calculate total outstanding for student including balance brought forward
        return \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
    }

    /**
     * Automated reminder job - send reminders for due fees and installments
     */
    public function sendAutomatedReminders()
    {
        $daysBeforeDue = [7, 3, 1]; // Remind 7 days, 3 days, 1 day before due
        $daysAfterOverdue = [1, 3, 7]; // Remind 1, 3, 7 days after overdue

        // Process invoice-based reminders
        foreach ($daysBeforeDue as $days) {
            $dueDate = now()->addDays($days)->format('Y-m-d');

            // Find students with outstanding fees due on this date
            $invoices = Invoice::where('status', '!=', 'reversed')
                ->whereHas('student', function($q) {
                    $q->whereNotNull('parent_id');
                })
                ->with(['student.parent', 'payments'])
                ->get()
                ->filter(function($invoice) use ($dueDate) {
                    // Check if invoice is due on this date
                    $invoiceDueDate = $this->getInvoiceDueDate($invoice);
                    return $invoiceDueDate === $dueDate && $this->hasOutstanding($invoice);
                });

            foreach ($invoices as $invoice) {
                $student = $invoice->student;
                $outstanding = $this->calculateOutstanding($student, $invoice->id);

                // Check if reminder already sent for this date
                $existing = FeeReminder::where('student_id', $student->id)
                    ->where('invoice_id', $invoice->id)
                    ->where('days_before_due', $days)
                    ->where('reminder_rule', 'before_due')
                    ->where('status', 'sent')
                    ->exists();

                if (!$existing && $outstanding > 0) {
                    $reminder = FeeReminder::create([
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'channel' => 'both',
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

        // Process installment-based reminders (before due)
        foreach ($daysBeforeDue as $days) {
            $dueDate = now()->addDays($days)->format('Y-m-d');

            $installments = \App\Models\FeePaymentPlanInstallment::where('due_date', $dueDate)
                ->whereIn('status', ['pending', 'partial'])
                ->whereHas('paymentPlan.student', function($q) {
                    $q->whereNotNull('parent_id');
                })
                ->with(['paymentPlan.student.parent', 'paymentPlan'])
                ->get();

            foreach ($installments as $installment) {
                $plan = $installment->paymentPlan;
                $student = $plan->student;
                $outstanding = $installment->amount - $installment->paid_amount;

                // Check if reminder already sent for this installment and rule
                $existing = FeeReminder::where('student_id', $student->id)
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
                        'channel' => 'both',
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

        // Process installment reminders on due date
        $today = now()->format('Y-m-d');
        $installmentsDueToday = \App\Models\FeePaymentPlanInstallment::where('due_date', $today)
            ->whereIn('status', ['pending', 'partial'])
            ->whereHas('paymentPlan.student', function($q) {
                $q->whereNotNull('parent_id');
            })
            ->with(['paymentPlan.student.parent', 'paymentPlan'])
            ->get();

        foreach ($installmentsDueToday as $installment) {
            $plan = $installment->paymentPlan;
            $student = $plan->student;
            $outstanding = $installment->amount - $installment->paid_amount;

            // Check if reminder already sent for this installment on due date
            $existing = FeeReminder::where('student_id', $student->id)
                ->where('payment_plan_installment_id', $installment->id)
                ->where('reminder_rule', 'on_due')
                ->where('status', 'sent')
                ->exists();

            if (!$existing && $outstanding > 0) {
                $reminder = FeeReminder::create([
                    'student_id' => $student->id,
                    'payment_plan_id' => $plan->id,
                    'payment_plan_installment_id' => $installment->id,
                    'channel' => 'both',
                    'outstanding_amount' => $outstanding,
                    'due_date' => $today,
                    'days_before_due' => 0,
                    'reminder_rule' => 'on_due',
                    'status' => 'pending',
                ]);

                $this->sendReminder($reminder);
            }
        }

        // Process overdue installment reminders
        foreach ($daysAfterOverdue as $days) {
            $overdueDate = now()->subDays($days)->format('Y-m-d');

            $overdueInstallments = \App\Models\FeePaymentPlanInstallment::where('due_date', $overdueDate)
                ->whereIn('status', ['overdue', 'partial'])
                ->whereHas('paymentPlan.student', function($q) {
                    $q->whereNotNull('parent_id');
                })
                ->with(['paymentPlan.student.parent', 'paymentPlan'])
                ->get()
                ->filter(function($installment) {
                    return ($installment->amount - $installment->paid_amount) > 0;
                });

            foreach ($overdueInstallments as $installment) {
                $plan = $installment->paymentPlan;
                $student = $plan->student;
                $outstanding = $installment->amount - $installment->paid_amount;

                // Check if reminder already sent for this installment and days after
                $existing = FeeReminder::where('student_id', $student->id)
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
                        'channel' => 'both',
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

        return response()->json(['message' => 'Automated reminders sent']);
    }

    protected function getInvoiceDueDate(Invoice $invoice)
    {
        // Use term end date or invoice date + 30 days as default
        $term = \App\Models\Term::where('name', $invoice->term)->first();
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

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeReminder;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\SMSService;
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

        $message = $reminder->message ?? $this->generateDefaultMessage($reminder);

        if ($reminder->channel === 'email' || $reminder->channel === 'both') {
            $email = null;
            if ($parent) {
                $email = $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
            }
            
            if ($email) {
                try {
                    Mail::to($email)->send(new GenericMail(
                        'Fee Payment Reminder',
                        $message
                    ));
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
                $phone = $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
            }
            $phone = $phone ?? $student->phone_number ?? null;
            
            if ($phone) {
                try {
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
     * Automated reminder job - send reminders for due fees
     */
    public function sendAutomatedReminders()
    {
        $daysBeforeDue = [7, 3, 1, 0]; // Remind 7 days, 3 days, 1 day, and on due date

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

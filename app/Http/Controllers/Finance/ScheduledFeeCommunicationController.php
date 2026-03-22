<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ScheduledFeeCommunication;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Services\CommunicationHelperService;
use App\Services\StudentBalanceService;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;

class ScheduledFeeCommunicationController extends Controller
{
    public function index(Request $request)
    {
        $query = ScheduledFeeCommunication::with(['student', 'template', 'createdBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $scheduled = $query->paginate(20)->withQueryString();

        return view('finance.fee_reminders.schedule.index', compact('scheduled'));
    }

    public function create()
    {
        $templates = CommunicationTemplate::whereIn('type', ['email', 'sms', 'whatsapp'])
            ->orWhere('code', 'like', 'finance_%')
            ->orderBy('title')
            ->get();

        $classrooms = Classroom::orderBy('name')->get();

        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = class_exists(\App\Models\CustomPlaceholder::class)
            ? \App\Models\CustomPlaceholder::all()
            : collect();

        return view('finance.fee_reminders.schedule.create', compact('templates', 'classrooms', 'students', 'systemPlaceholders', 'customPlaceholders'));
    }

    protected function getSystemPlaceholders(): array
    {
        return [
            ['key' => 'school_name', 'value' => setting('school_name') ?? 'School Name'],
            ['key' => 'school_phone', 'value' => setting('school_phone') ?? 'School Phone'],
            ['key' => 'date', 'value' => now()->format('d M Y')],
            ['key' => 'student_name', 'value' => "Student's full name"],
            ['key' => 'admission_number', 'value' => 'Student admission number'],
            ['key' => 'class_name', 'value' => 'Class name'],
            ['key' => 'parent_name', 'value' => "Parent's full name"],
            ['key' => 'father_name', 'value' => "Parent's full name"],
            ['key' => 'outstanding_amount', 'value' => 'Outstanding fee balance'],
            ['key' => 'invoice_number', 'value' => 'Invoice number'],
            ['key' => 'total_amount', 'value' => 'Total invoice amount'],
            ['key' => 'due_date', 'value' => 'Due date'],
            ['key' => 'invoice_link', 'value' => 'Public invoice/payment link'],
            ['key' => 'finance_portal_link', 'value' => 'Student statement link (finance portal)'],
            ['key' => 'swimming_balance', 'value' => 'Swimming wallet balance (when applicable)'],
            ['key' => 'payment_plan_link', 'value' => 'Payment plan link'],
            ['key' => 'profile_update_link', 'value' => 'Profile update link for parents'],
        ];
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Scheduled fee communication store request', [
            'target' => $request->input('target'),
            'send_now' => $request->boolean('send_now'),
        ]);

        $rules = [
            'target' => 'required|in:one_parent,specific_students,class,all',
            'student_id' => 'nullable|required_if:target,one_parent|exists:students,id',
            'selected_student_ids' => 'nullable|required_if:target,specific_students|string',
            'classroom_ids' => 'nullable|required_if:target,class|array',
            'classroom_ids.*' => 'integer|exists:classrooms,id',
            'filter_type' => 'required|in:all,outstanding_fees,upcoming_invoices,swimming_balance',
            'balance_min' => 'nullable|numeric|min:0',
            'balance_max' => 'nullable|numeric|min:0',
            'balance_percent_min' => 'nullable|numeric|min:0|max:100',
            'balance_percent_max' => 'nullable|numeric|min:0|max:100',
            'channels' => 'required|array',
            'channels.*' => 'in:sms,email,whatsapp',
            'template_id' => 'nullable|exists:communication_templates,id',
            'custom_message' => 'nullable|string',
            'recurrence_type' => 'required|in:once,daily,weekly,times_per_day',
            'recurrence_times' => 'nullable|array',
            'recurrence_times.*' => 'string|regex:/^\d{1,2}:\d{2}$/',
            'recurrence_week_days' => 'nullable|array',
            'recurrence_week_days.*' => 'integer|min:0|max:6',
            'recurrence_start_at' => 'nullable|date',
            'recurrence_end_at' => 'nullable|date|after:recurrence_start_at',
            'send_at' => 'nullable|date',
            'send_now' => 'nullable|boolean',
            'exclude_staff' => 'nullable|boolean',
            'exclude_student_ids' => 'nullable|string',
        ];

        $validated = $request->validate($rules);
        $sendNow = (bool) ($request->input('send_now') ?? false);

        if ($validated['recurrence_type'] === 'once' && !$sendNow) {
            $request->validate(['send_at' => 'required|date|after:now']);
        } elseif (!$sendNow) {
            $request->validate([
                'recurrence_start_at' => 'required|date|after:now',
                'recurrence_times' => 'required|array|min:1',
                'recurrence_times.*' => 'string|regex:/^\d{1,2}:\d{2}$/',
            ]);
            if ($validated['recurrence_type'] === 'weekly') {
                $request->validate(['recurrence_week_days' => 'required|array|min:1']);
            }
        }

        if (empty($validated['channels'])) {
            return back()->withInput()->withErrors(['channels' => 'Select at least one channel (SMS, Email, or WhatsApp).']);
        }

        $message = trim((string) ($validated['custom_message'] ?? ''));
        if ($message === '' && ($validated['template_id'] ?? null)) {
            $tpl = CommunicationTemplate::find($validated['template_id']);
            $message = $tpl ? trim((string) ($tpl->content ?? '')) : '';
        }
        if ($message === '') {
            return back()->withInput()->withErrors(['custom_message' => 'Please provide a message or select a template.']);
        }

        // Ensure template type matches at least one selected channel (when using template)
        if (!empty($validated['template_id'])) {
            $tpl = CommunicationTemplate::find($validated['template_id']);
            if ($tpl) {
                $tplType = strtolower($tpl->type ?? 'email');
                $channels = array_map('strtolower', $validated['channels']);
                $compatible = in_array($tplType, $channels)
                    || ($tplType === 'sms' && in_array('whatsapp', $channels));
                if (!$compatible) {
                    return back()->withInput()->withErrors(['template_id' => 'Selected template type (' . ucfirst($tplType) . ') does not match your chosen channels.']);
                }
            }
        }

        if ($validated['target'] === 'class' && empty($validated['classroom_ids'])) {
            return back()->withInput()->withErrors(['classroom_ids' => 'Please select at least one class.']);
        }

        if ($validated['target'] === 'specific_students') {
            $ids = array_filter(array_map('intval', explode(',', (string) ($validated['selected_student_ids'] ?? ''))));
            if (empty($ids)) {
                return back()->withInput()->withErrors(['selected_student_ids' => 'Please select at least one student.']);
            }
            $validated['selected_student_ids'] = $ids;
        } else {
            $validated['selected_student_ids'] = null;
        }

        $validated['classroom_ids'] = $validated['classroom_ids'] ?? null;

        // Exclude options (apply when target is "all")
        $validated['exclude_staff'] = $validated['target'] === 'all'
            ? (bool) ($request->input('exclude_staff', true))
            : true;
        $validated['exclude_student_ids'] = $validated['target'] === 'all' && $request->filled('exclude_student_ids')
            ? array_filter(array_map('intval', explode(',', (string) $request->exclude_student_ids)))
            : null;
        $validated['created_by'] = auth()->id();

        if ($sendNow) {
            $validated['recurrence_type'] = 'once';
            $validated['send_at'] = now();
            $validated['recurrence_times'] = null;
            $validated['recurrence_week_days'] = null;
            $validated['recurrence_start_at'] = null;
            $validated['recurrence_end_at'] = null;
            $validated['recurrence_next_at'] = null;
        }

        if ($validated['recurrence_type'] === 'once' && !$sendNow) {
            $validated['recurrence_times'] = null;
            $validated['recurrence_week_days'] = null;
            $validated['recurrence_start_at'] = null;
            $validated['recurrence_end_at'] = null;
            $validated['recurrence_next_at'] = null;
        } else {
            $validated['send_at'] = $validated['recurrence_start_at'];
            $validated['recurrence_next_at'] = $this->computeFirstRecurrence(
                $validated['recurrence_type'],
                $validated['recurrence_times'] ?? ['09:00'],
                $validated['recurrence_week_days'] ?? [1],
                $validated['recurrence_start_at'],
                $validated['recurrence_end_at'] ?? null
            );
            $validated['recurrence_week_days'] = $validated['recurrence_type'] === 'weekly'
                ? ($validated['recurrence_week_days'] ?? [1])
                : null;
        }

        try {
            $item = ScheduledFeeCommunication::create($validated);

            $sendJobError = null;
            if ($sendNow) {
                try {
                    \App\Jobs\ProcessScheduledFeeCommunicationsJob::dispatchSync();
                } catch (\Throwable $jobEx) {
                    \Illuminate\Support\Facades\Log::error('ProcessScheduledFeeCommunicationsJob failed on send now', [
                        'error' => $jobEx->getMessage(),
                        'trace' => $jobEx->getTraceAsString(),
                    ]);
                    $sendJobError = $jobEx->getMessage();
                }
            }

            $msg = $sendNow
                ? ($sendJobError
                    ? 'Communication saved. Sending failed: ' . $sendJobError . ' Check logs. Run: php artisan queue:work'
                    : 'Communication sent successfully. Parents with balances have been notified.')
                : ($validated['recurrence_type'] === 'once'
                ? 'Communication scheduled successfully. It will be sent automatically at the scheduled time.'
                : 'Recurring communication scheduled. Balances are checked fresh each time before sending—parents who have paid will not receive the message.');

            // Always redirect to Scheduled tab so user sees their item (Sent tab shows different FeeReminder data)
            return redirect()->route('finance.fee-reminders.index', ['tab' => 'scheduled'])
                ->with($sendJobError ? 'error' : 'success', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Scheduled fee communication store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    protected function computeFirstRecurrence(string $type, array $times, array $weekDays, string $startAt, ?string $endAt): ?\Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($startAt);
        $end = $endAt ? \Carbon\Carbon::parse($endAt) : null;

        if ($type === 'daily' || $type === 'times_per_day') {
            $base = $start->copy()->startOfDay();
            foreach ($times as $t) {
                $parts = array_pad(explode(':', $t), 2, 0);
                $candidate = $base->copy()->setTime((int) $parts[0], (int) $parts[1]);
                if ($candidate->gte($start) && (!$end || $candidate->lte($end))) {
                    return $candidate;
                }
            }
            $parts = array_pad(explode(':', $times[0]), 2, 0);
            $candidate = $base->copy()->addDay()->setTime((int) $parts[0], (int) $parts[1]);
            return (!$end || $candidate->lte($end)) ? $candidate : null;
        }

        if ($type === 'weekly') {
            $base = $start->copy()->startOfDay();
            for ($i = 0; $i <= 7; $i++) {
                $check = $base->copy()->addDays($i);
                $dayOfWeek = (int) $check->format('w');
                if (!in_array($dayOfWeek, $weekDays)) {
                    continue;
                }
                foreach ($times as $t) {
                    $parts = array_pad(explode(':', $t), 2, 0);
                    $candidate = $check->copy()->setTime((int) $parts[0], (int) $parts[1]);
                    if ($candidate->gte($start) && (!$end || $candidate->lte($end))) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    public function destroy(ScheduledFeeCommunication $scheduledFeeCommunication)
    {
        if (!in_array($scheduledFeeCommunication->status, ['pending', 'active'])) {
            return back()->with('error', 'Only pending or active scheduled communications can be cancelled.');
        }

        $scheduledFeeCommunication->update(['status' => 'cancelled']);

        return back()->with('success', 'Scheduled communication cancelled.');
    }

    public function previewCount(Request $request)
    {
        $excludeIds = $request->input('exclude_student_ids');
        if (is_string($excludeIds)) {
            $excludeIds = array_filter(array_map('intval', explode(',', $excludeIds)));
        }

        $data = [
            'target' => $request->input('target'),
            'student_id' => $request->input('student_id'),
            'selected_student_ids' => $request->input('selected_student_ids'),
            'classroom_ids' => $request->input('classroom_ids'),
            'exclude_staff' => (bool) $request->input('exclude_staff', true),
            'exclude_student_ids' => !empty($excludeIds) ? $excludeIds : null,
        ];

        $ids = $request->input('classroom_ids');
        if (is_string($ids)) {
            $data['classroom_ids'] = array_filter(array_map('intval', explode(',', $ids)));
        }

        $sid = $request->input('selected_student_ids');
        if (is_string($sid)) {
            $data['selected_student_ids'] = array_filter(array_map('intval', explode(',', $sid)));
        }

        switch ($request->input('filter_type')) {
            case 'outstanding_fees':
                $data['fee_balance_only'] = true;
                break;
            case 'upcoming_invoices':
                $data['upcoming_invoices_only'] = true;
                break;
            case 'swimming_balance':
                $data['swimming_balance_only'] = true;
                break;
        }

        if ($request->filled('balance_min') && (float) $request->balance_min > 0) {
            if ($request->input('filter_type') === 'swimming_balance') {
                $data['swimming_balance_min'] = (float) $request->balance_min;
            } else {
                $data['fee_balance_min'] = (float) $request->balance_min;
            }
        }
        if ($request->filled('balance_percent_min') && (float) $request->balance_percent_min > 0) {
            $data['fee_balance_percent_min'] = (float) $request->balance_percent_min;
        }

        $emailRecipients = CommunicationHelperService::collectRecipients($data, 'email');
        $count = count(CommunicationHelperService::expandRecipientsToPairs($emailRecipients));

        return response()->json(['count' => $count]);
    }

    /**
     * Preview recipients with student name, parent contact, and fee balance.
     */
    public function previewRecipients(Request $request)
    {
        $excludeIds = $request->input('exclude_student_ids');
        if (is_string($excludeIds)) {
            $excludeIds = array_filter(array_map('intval', explode(',', $excludeIds)));
        }

        $data = [
            'target' => $request->input('target'),
            'student_id' => $request->input('student_id'),
            'selected_student_ids' => $request->input('selected_student_ids'),
            'classroom_ids' => $request->input('classroom_ids'),
            'exclude_staff' => (bool) $request->input('exclude_staff', true),
            'exclude_student_ids' => !empty($excludeIds) ? $excludeIds : null,
        ];

        $ids = $request->input('classroom_ids');
        if (is_string($ids)) {
            $data['classroom_ids'] = array_filter(array_map('intval', explode(',', $ids)));
        }

        $sid = $request->input('selected_student_ids');
        if (is_string($sid)) {
            $data['selected_student_ids'] = array_filter(array_map('intval', explode(',', $sid)));
        }

        switch ($request->input('filter_type')) {
            case 'outstanding_fees':
                $data['fee_balance_only'] = true;
                break;
            case 'upcoming_invoices':
                $data['upcoming_invoices_only'] = true;
                break;
            case 'swimming_balance':
                $data['swimming_balance_only'] = true;
                break;
        }

        if ($request->filled('balance_min') && (float) $request->balance_min > 0) {
            if ($request->input('filter_type') === 'swimming_balance') {
                $data['swimming_balance_min'] = (float) $request->balance_min;
            } else {
                $data['fee_balance_min'] = (float) $request->balance_min;
            }
        }
        if ($request->filled('balance_percent_min') && (float) $request->balance_percent_min > 0) {
            $data['fee_balance_percent_min'] = (float) $request->balance_percent_min;
        }

        // Only collect from selected channels (default to all if none selected)
        $channels = $request->input('channels');
        if (empty($channels) || !is_array($channels)) {
            $channels = ['email', 'sms', 'whatsapp'];
        }
        $channels = array_intersect($channels, ['email', 'sms', 'whatsapp']);

        $allPairs = [];
        foreach ($channels as $channel) {
            $channelRecipients = CommunicationHelperService::collectRecipients($data, $channel);
            $allPairs = array_merge($allPairs, CommunicationHelperService::expandRecipientsToPairs($channelRecipients));
        }
        // Dedupe by contact+student_id to avoid showing same pair twice
        $seen = [];
        $pairs = [];
        foreach ($allPairs as [$contact, $entity]) {
            if (!$entity instanceof Student) {
                continue;
            }
            $key = $contact . '-' . $entity->id;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $pairs[] = [$contact, $entity];
            }
        }

        $recipients = [];
        foreach ($pairs as [$contact, $entity]) {
            $recipients[] = [
                'student_name' => $entity->full_name ?? ($entity->first_name . ' ' . $entity->last_name),
                'admission_number' => $entity->admission_number ?? $entity->admission_no ?? '-',
                'parent_contact' => $contact,
                'fee_balance' => number_format(StudentBalanceService::getTotalOutstandingBalance($entity, true), 2),
            ];
        }

        return response()->json([
            'count' => count($recipients),
            'recipients' => $recipients,
        ]);
    }
}

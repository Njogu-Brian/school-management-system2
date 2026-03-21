<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ScheduledFeeCommunication;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Services\CommunicationHelperService;
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
        $templates = CommunicationTemplate::whereIn('type', ['email', 'sms'])
            ->orWhere('code', 'like', 'finance_%')
            ->orderBy('title')
            ->get();

        $classrooms = Classroom::orderBy('name')->get();

        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        return view('finance.fee_reminders.schedule.create', compact('templates', 'classrooms', 'students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'send_at' => 'required|date|after:now',
        ]);

        if (empty($validated['channels'])) {
            return back()->withInput()->withErrors(['channels' => 'Select at least one channel (SMS, Email, or WhatsApp).']);
        }

        $message = $validated['custom_message'] ?? null;
        if (empty($message) && $validated['template_id']) {
            $tpl = CommunicationTemplate::find($validated['template_id']);
            $message = $tpl ? $tpl->content : null;
        }
        if (empty($message)) {
            return back()->withInput()->withErrors(['custom_message' => 'Please provide a message or select a template.']);
        }

        if ($validated['target'] === 'class' && empty($validated['classroom_ids'])) {
            return back()->withInput()->withErrors(['classroom_ids' => 'Please select at least one class.']);
        }

        if ($validated['target'] === 'specific_students') {
            $ids = array_filter(array_map('intval', explode(',', (string) $validated['selected_student_ids'])));
            if (empty($ids)) {
                return back()->withInput()->withErrors(['selected_student_ids' => 'Please select at least one student.']);
            }
            $validated['selected_student_ids'] = $ids;
        } else {
            $validated['selected_student_ids'] = null;
        }

        $validated['classroom_ids'] = $validated['classroom_ids'] ?? null;
        $validated['created_by'] = auth()->id();

        ScheduledFeeCommunication::create($validated);

        return redirect()->route('finance.fee-reminders.schedule.index')
            ->with('success', 'Communication scheduled successfully. It will be sent automatically at the scheduled time.');
    }

    public function destroy(ScheduledFeeCommunication $scheduledFeeCommunication)
    {
        if ($scheduledFeeCommunication->status !== 'pending') {
            return back()->with('error', 'Only pending scheduled communications can be cancelled.');
        }

        $scheduledFeeCommunication->update(['status' => 'cancelled']);

        return back()->with('success', 'Scheduled communication cancelled.');
    }

    public function previewCount(Request $request)
    {
        $data = [
            'target' => $request->input('target'),
            'student_id' => $request->input('student_id'),
            'selected_student_ids' => $request->input('selected_student_ids'),
            'classroom_ids' => $request->input('classroom_ids'),
            'exclude_staff' => true,
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
}

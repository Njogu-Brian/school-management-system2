<?php

namespace App\Http\Controllers;

use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationTemplateController extends Controller
{
    public function index()
    {
        $templates = CommunicationTemplate::latest()->paginate(20);
        return view('communication.templates.index', compact('templates'));
    }

    public function create()
    {
        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = \App\Models\CustomPlaceholder::all();
        return view('communication.templates.create', compact('systemPlaceholders', 'customPlaceholders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => ['required','string','max:100','unique:communication_templates,code'],
            'title'      => ['required','string','max:255'],
            'type'       => ['required', Rule::in(['email','sms','whatsapp'])],
            'subject'    => ['nullable','string','max:255'],
            'content'    => ['required','string'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('communication_attachments', 'public');
        }

        CommunicationTemplate::create($data);

        return redirect()->route('communication-templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(CommunicationTemplate $communication_template)
    {
        $template = $communication_template;
        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = \App\Models\CustomPlaceholder::all();
        return view('communication.templates.edit', compact('template', 'systemPlaceholders', 'customPlaceholders'));
    }

    /**
     * Get system placeholders (same as in settings)
     */
    protected function getSystemPlaceholders()
    {
        return [
            // General
            ['key' => 'school_name',  'value' => setting('school_name') ?? 'School Name'],
            ['key' => 'school_phone', 'value' => setting('school_phone') ?? 'School Phone'],
            ['key' => 'date',         'value' => now()->format('d M Y')],
            
            // Student & Parent
            ['key' => 'student_name', 'value' => 'Student\'s full name'],
            ['key' => 'admission_number', 'value' => 'Student admission number'],
            ['key' => 'class_name',   'value' => 'Classroom name'],
            ['key' => 'parent_name',  'value' => 'Parent\'s full name'],
            ['key' => 'father_name',  'value' => 'Parent\'s full name'],
            
            // Staff
            ['key' => 'staff_name',   'value' => 'Staff full name'],
            
            // Receipts
            ['key' => 'receipt_number', 'value' => 'Receipt number (e.g., RCPT-2024-001)'],
            ['key' => 'transaction_code', 'value' => 'Transaction code (e.g., TXN-20241217-ABC123)'],
            ['key' => 'payment_date', 'value' => 'Payment date (e.g., 17 Dec 2024)'],
            ['key' => 'amount', 'value' => 'Payment amount (e.g., 5,000.00)'],
            ['key' => 'receipt_link', 'value' => 'Public receipt link (10-char token)'],
            ['key' => 'carried_forward', 'value' => 'Carried forward amount (unallocated payment)'],
            
            // Invoices & Reminders
            ['key' => 'invoice_number', 'value' => 'Invoice number (e.g., INV-2024-001)'],
            ['key' => 'total_amount', 'value' => 'Total invoice amount (e.g., 15,000.00)'],
            ['key' => 'due_date', 'value' => 'Due date (e.g., 31 Dec 2024)'],
            ['key' => 'outstanding_amount', 'value' => 'Outstanding balance amount'],
            ['key' => 'status', 'value' => 'Invoice status (paid, partial, unpaid)'],
            ['key' => 'invoice_link', 'value' => 'Public invoice link (10-char hash)'],
            ['key' => 'days_overdue', 'value' => 'Number of days overdue'],
            
            // Payment Plans
            ['key' => 'installment_count', 'value' => 'Number of installments'],
            ['key' => 'installment_amount', 'value' => 'Amount per installment'],
            ['key' => 'installment_number', 'value' => 'Current installment number'],
            ['key' => 'start_date', 'value' => 'Payment plan start date'],
            ['key' => 'end_date', 'value' => 'Payment plan end date'],
            ['key' => 'remaining_installments', 'value' => 'Number of remaining installments'],
            ['key' => 'payment_plan_link', 'value' => 'Public payment plan link (10-char hash)'],
            
            // Custom Finance
            ['key' => 'custom_message', 'value' => 'Custom message content'],
            ['key' => 'custom_subject', 'value' => 'Custom email subject'],
        ];
    }

    public function update(Request $request, CommunicationTemplate $communication_template)
    {
        $template = $communication_template;

        $data = $request->validate([
            'code'       => ['required','string','max:100', Rule::unique('communication_templates','code')->ignore($template->id)],
            'title'      => ['required','string','max:255'],
            'type'       => ['required', Rule::in(['email','sms','whatsapp'])],
            'subject'    => ['nullable','string','max:255'],
            'content'    => ['required','string'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('communication_attachments', 'public');
        }

        $template->update($data);

        return redirect()->route('communication-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(CommunicationTemplate $communication_template)
    {
        $communication_template->delete();

        return redirect()->route('communication-templates.index')
            ->with('success', 'Template deleted.');
    }
}

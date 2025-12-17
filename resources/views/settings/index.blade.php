@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">⚙️ System Settings</h4>

    <ul class="nav nav-tabs" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">General Info</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="branding-tab" data-bs-toggle="tab" href="#branding" role="tab">Branding</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="regional-tab" data-bs-toggle="tab" href="#regional" role="tab">Regional Settings</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="ids-tab" data-bs-toggle="tab" href="#ids" role="tab">ID Settings</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="ids-tab" data-bs-toggle="tab" href="#features" role="tab">Features</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="placeholders-tab" data-bs-toggle="tab" href="#placeholders" role="tab">Placeholders</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="ids-tab" data-bs-toggle="tab" href="#modules" role="tab">Modules</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="system-tab" data-bs-toggle="tab" href="#system" role="tab">System Options</a>
        </li>
        
    </ul>

    <div class="tab-content p-4 border border-top-0 rounded-bottom" id="settingsTabContent">
        @include('settings.partials.general')
        @include('settings.partials.branding')
        @include('settings.partials.regional')
        @include('settings.partials.ids')
        @include('settings.partials.system')
        @include('settings.partials.features')
        @include('settings.partials.modules')
        @include('settings.partials.placeholders', [
        'systemPlaceholders' => [
            // General
            ['key' => 'school_name',  'value' => setting('school_name') ?? 'School Name'],
            ['key' => 'school_phone', 'value' => setting('school_phone') ?? 'School Phone'],
            ['key' => 'date',         'value' => now()->format('d M Y')],
            
            // Student & Parent
            ['key' => 'student_name', 'value' => 'Student's full name'],
            ['key' => 'admission_number', 'value' => 'Student admission number'],
            ['key' => 'class_name',   'value' => 'Classroom name'],
            ['key' => 'parent_name',  'value' => 'Parent's full name'],
            ['key' => 'father_name',  'value' => 'Parent's full name'],
            
            // Staff
            ['key' => 'staff_name',   'value' => 'Staff full name'],
            
            // Receipts
            ['key' => 'receipt_number', 'value' => 'Receipt number (e.g., RCPT-2024-001)'],
            ['key' => 'transaction_code', 'value' => 'Transaction code (e.g., TXN-20241217-ABC123)'],
            ['key' => 'payment_date', 'value' => 'Payment date (e.g., 17 Dec 2024)'],
            ['key' => 'amount', 'value' => 'Payment amount (e.g., 5,000.00)'],
            ['key' => 'receipt_link', 'value' => 'Public receipt link (10-char token)'],
            
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
        ],
        'customPlaceholders' => \App\Models\CustomPlaceholder::all(),
    ])

    </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="crumb">Settings / System</div>
                <h1>System Settings</h1>
                <p>Align your school's identity, regional defaults, modules, and finance theme.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="settings-chip"><i class="bi bi-shield-check"></i> Admin access only</span>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <a href="{{ route('settings.academic.index') }}" class="btn btn-ghost-strong btn-sm text-nowrap">
                    <i class="bi bi-calendar3"></i> Academic Calendar
                </a>
                <a href="{{ route('settings.school-days.index') }}" class="btn btn-ghost-strong btn-sm text-nowrap">
                    <i class="bi bi-calendar-week"></i> School Days
                </a>
            </div>
        </div>

        <ul class="nav nav-pills settings-tabs" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab">
                    <i class="bi bi-building"></i> General Info
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-branding-tab" data-bs-toggle="pill" data-bs-target="#tab-branding" type="button" role="tab">
                    <i class="bi bi-brush"></i> Branding
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-regional-tab" data-bs-toggle="pill" data-bs-target="#tab-regional" type="button" role="tab">
                    <i class="bi bi-geo-alt"></i> Regional
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ids-tab" data-bs-toggle="pill" data-bs-target="#tab-ids" type="button" role="tab">
                    <i class="bi bi-upc-scan"></i> ID Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-placeholders-tab" data-bs-toggle="pill" data-bs-target="#tab-placeholders" type="button" role="tab">
                    <i class="bi bi-braces"></i> Placeholders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-modules-tab" data-bs-toggle="pill" data-bs-target="#tab-modules" type="button" role="tab">
                    <i class="bi bi-sliders"></i> Modules & Features
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-system-tab" data-bs-toggle="pill" data-bs-target="#tab-system" type="button" role="tab">
                    <i class="bi bi-gear"></i> System Options
                </button>
            </li>
        </ul>

        <div class="tab-content tab-surface" id="settingsTabContent">
            @include('settings.partials.general')
            @include('settings.partials.branding')
            @include('settings.partials.regional')
            @include('settings.partials.ids')
            @include('settings.partials.modules')
            @include('settings.partials.system')
            @include('settings.partials.placeholders', [
                'systemPlaceholders' => [
                    ['key' => 'school_name',  'value' => setting('school_name') ?? 'School Name'],
                    ['key' => 'school_phone', 'value' => setting('school_phone') ?? 'School Phone'],
                    ['key' => 'date',         'value' => now()->format('d M Y')],

                    ['key' => 'student_name', 'value' => 'Student\'s full name'],
                    ['key' => 'admission_number', 'value' => 'Student admission number'],
                    ['key' => 'class_name',   'value' => 'Classroom name'],
                    ['key' => 'parent_name',  'value' => 'Parent\'s full name'],
                    ['key' => 'father_name',  'value' => 'Parent\'s full name'],

                    ['key' => 'staff_name',   'value' => 'Staff full name'],

                    ['key' => 'receipt_number', 'value' => 'Receipt number (e.g., RCPT-2024-001)'],
                    ['key' => 'transaction_code', 'value' => 'Transaction code (e.g., TXN-20241217-ABC123)'],
                    ['key' => 'payment_date', 'value' => 'Payment date (e.g., 17 Dec 2024)'],
                    ['key' => 'amount', 'value' => 'Payment amount (e.g., 5,000.00)'],
                    ['key' => 'receipt_link', 'value' => 'Public receipt link (10-char token)'],

                    ['key' => 'invoice_number', 'value' => 'Invoice number (e.g., INV-2024-001)'],
                    ['key' => 'total_amount', 'value' => 'Total invoice amount (e.g., 15,000.00)'],
                    ['key' => 'due_date', 'value' => 'Due date (e.g., 31 Dec 2024)'],
                    ['key' => 'outstanding_amount', 'value' => 'Outstanding balance amount'],
                    ['key' => 'status', 'value' => 'Invoice status (paid, partial, unpaid)'],
                    ['key' => 'invoice_link', 'value' => 'Public invoice link (10-char hash)'],
                    ['key' => 'days_overdue', 'value' => 'Number of days overdue'],

                    ['key' => 'installment_count', 'value' => 'Number of installments'],
                    ['key' => 'installment_amount', 'value' => 'Amount per installment'],
                    ['key' => 'installment_number', 'value' => 'Current installment number'],
                    ['key' => 'start_date', 'value' => 'Payment plan start date'],
                    ['key' => 'end_date', 'value' => 'Payment plan end date'],
                    ['key' => 'remaining_installments', 'value' => 'Number of remaining installments'],
                    ['key' => 'payment_plan_link', 'value' => 'Public payment plan link (10-char hash)'],

                    ['key' => 'custom_message', 'value' => 'Custom message content'],
                    ['key' => 'custom_subject', 'value' => 'Custom email subject'],
                ],
                'customPlaceholders' => \App\Models\CustomPlaceholder::all(),
            ])
        </div>
    </div>
</div>
@endsection

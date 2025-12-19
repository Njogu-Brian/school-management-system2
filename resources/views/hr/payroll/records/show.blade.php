@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payroll Records</div>
                <h1 class="mb-1">Payroll Record Details</h1>
                <p class="text-muted mb-0">Full breakdown for {{ $record->staff->name }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="pill-badge {{ $record->status === 'approved' ? 'pill-success' : ($record->status === 'paid' ? 'pill-info' : 'pill-warning') }}">
                    {{ ucfirst($record->status) }}
                </span>
                <a href="{{ route('hr.payroll.records.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Records
                </a>
                <a href="{{ route('hr.payroll.records.payslip', $record->id) }}" class="btn btn-settings-primary" target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> View Payslip
                </a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Payroll Information</h5>
                    <p class="text-muted small mb-0">Period context and staff meta.</p>
                </div>
                <span class="pill-badge pill-secondary">Ref #{{ $record->id }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Staff Member</label>
                        <div class="fw-semibold">{{ $record->staff->name }}</div>
                        <div class="small text-muted">ID: {{ $record->staff->staff_id }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Payroll Period</label>
                        <div class="fw-semibold">{{ $record->payrollPeriod->period_name }}</div>
                        <div class="small text-muted">Pay Date: {{ $record->payrollPeriod->pay_date->format('M d, Y') }}</div>
                    </div>
                </div>

                <div class="divider"></div>

                <h6 class="mb-3">Salary Breakdown</h6>
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Basic Salary</label>
                        <div class="h5 text-primary mb-0">Ksh {{ number_format($record->basic_salary, 2) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Housing Allowance</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->housing_allowance, 2) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Transport Allowance</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->transport_allowance, 2) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Medical Allowance</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->medical_allowance, 2) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Other Allowances</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->other_allowances, 2) }}</div>
                    </div>
                    @if($record->bonus > 0)
                        <div class="col-md-6 mb-2">
                            <label class="text-muted small">Bonus</label>
                            <div class="h6 text-success mb-0">Ksh {{ number_format($record->bonus, 2) }}</div>
                        </div>
                    @endif
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Gross Salary</label>
                        <div class="h4 text-success mb-0">Ksh {{ number_format($record->gross_salary, 2) }}</div>
                    </div>
                </div>

                <div class="divider"></div>

                <h6 class="mb-3">Deductions</h6>
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small">NSSF</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->nssf_deduction, 2) }}</div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small">NHIF</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->nhif_deduction, 2) }}</div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small">PAYE</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->paye_deduction, 2) }}</div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small">Other Deductions</label>
                        <div class="h6 mb-0">Ksh {{ number_format($record->other_deductions, 2) }}</div>
                    </div>
                    @if($record->advance_deduction > 0)
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small">Advance Deduction</label>
                            <div class="h6 mb-0">Ksh {{ number_format($record->advance_deduction, 2) }}</div>
                        </div>
                    @endif
                    @if($record->custom_deductions_total > 0)
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small">Custom Deductions</label>
                            <div class="h6 mb-0">Ksh {{ number_format($record->custom_deductions_total, 2) }}</div>
                        </div>
                    @endif
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Total Deductions</label>
                        <div class="h5 text-danger mb-0">Ksh {{ number_format($record->total_deductions, 2) }}</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Net Salary</label>
                        <div class="h3 text-primary mb-0">Ksh {{ number_format($record->net_salary, 2) }}</div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Status</label>
                        <div>
                            <span class="pill-badge {{ $record->status === 'approved' ? 'pill-success' : ($record->status === 'paid' ? 'pill-info' : 'pill-warning') }}">
                                {{ ucfirst($record->status) }}
                            </span>
                        </div>
                    </div>
                    @if($record->payslip_number)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Payslip Number</label>
                            <div>{{ $record->payslip_number }}</div>
                        </div>
                    @endif
                    @if($record->notes)
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Notes</label>
                            <div class="alert alert-soft border-0 mb-0">{{ nl2br(e($record->notes)) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


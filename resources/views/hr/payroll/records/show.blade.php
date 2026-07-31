@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('hr.payroll.partials.styles')
@endpush

@section('content')
<div class="settings-page payroll-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payroll Records</div>
                <h1 class="mb-1">Payroll Record Details</h1>
                <p class="text-muted mb-0">Full breakdown for {{ $record->staff?->name ?? ('Staff #'.$record->staff_id) }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @php
                    $statusPill = match($record->status) {
                        'approved' => 'pill-success',
                        'paid' => 'pill-info',
                        'cancelled' => 'pill-danger',
                        default => 'pill-warning',
                    };
                @endphp
                <span class="pill-badge {{ $statusPill }}">
                    {{ ucfirst($record->status) }}
                </span>
                <a href="{{ route('hr.payroll.records.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Records
                </a>
                @if($record->status !== 'cancelled')
                    <a href="{{ route('hr.payroll.records.payslip', $record->id) }}" class="btn btn-settings-primary" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> View Payslip
                    </a>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        <div class="row g-3">
            <div class="col-lg-{{ $record->canEdit() || $record->canCancel() ? '8' : '12' }}">
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
                                <div class="fw-semibold">{{ $record->staff?->name ?? ('Staff #'.$record->staff_id) }}</div>
                                @if($record->staff?->staff_id)
                                    <div class="small text-muted">ID: {{ $record->staff->staff_id }}</div>
                                @endif
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
                                <label class="text-muted small">SHIF</label>
                                <div class="h6 mb-0">Ksh {{ number_format($record->shif_deduction ?? 0, 2) }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="text-muted small">PAYE</label>
                                <div class="h6 mb-0">Ksh {{ number_format($record->paye_deduction, 2) }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="text-muted small">Housing Levy</label>
                                <div class="h6 mb-0">Ksh {{ number_format($record->housing_levy_deduction ?? 0, 2) }}</div>
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
                                    <span class="pill-badge {{ $statusPill }}">
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
                            @if($record->adjustments_notes)
                                <div class="col-12 mb-3">
                                    <label class="text-muted small">Adjustment Notes</label>
                                    <div class="alert alert-soft border-0 mb-0">{{ nl2br(e($record->adjustments_notes)) }}</div>
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

            @if($record->canEdit() || $record->canCancel())
            <div class="col-lg-4">
                @if($record->canEdit())
                <div class="settings-card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Adjustments</h5>
                        <p class="text-muted small mb-0">Adjust bonus, deductions, and notes while the period is unlocked.</p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.payroll.records.update', $record->id) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <label class="form-label">Bonus (Ksh)</label>
                                <input type="number" name="bonus" step="0.01" min="0" class="form-control @error('bonus') is-invalid @enderror" value="{{ old('bonus', $record->bonus) }}">
                                @error('bonus')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Advance Deduction (Ksh)</label>
                                <input type="number" name="advance_deduction" step="0.01" min="0" class="form-control @error('advance_deduction') is-invalid @enderror" value="{{ old('advance_deduction', $record->advance_deduction) }}">
                                @error('advance_deduction')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Slip amount only — does not change advance balances.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Custom Deductions Total (Ksh)</label>
                                <input type="number" name="custom_deductions_total" step="0.01" min="0" class="form-control @error('custom_deductions_total') is-invalid @enderror" value="{{ old('custom_deductions_total', $record->custom_deductions_total) }}">
                                @error('custom_deductions_total')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Slip amount only — does not change deduction progress.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Adjustment Notes</label>
                                <textarea name="adjustments_notes" rows="2" class="form-control @error('adjustments_notes') is-invalid @enderror" placeholder="Why this adjustment was made">{{ old('adjustments_notes', $record->adjustments_notes) }}</textarea>
                                @error('adjustments_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $record->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-settings-primary w-100">
                                    <i class="bi bi-check-circle"></i> Save Adjustments
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                @if($record->canCancel())
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0 text-danger">Reject / Cancel Slip</h5>
                        <p class="text-muted small mb-0">Exclude this staff from this period without wiping other records.</p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.payroll.records.cancel', $record->id) }}" method="POST" onsubmit="return confirm('Cancel this payslip? Advance and custom deduction progress applied for this run will be reversed. Period totals will update.')">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reason" rows="2" class="form-control @error('reason') is-invalid @enderror" placeholder="e.g. Staff on unpaid leave this month">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-x-circle"></i> Cancel This Slip
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Salary Structures</div>
                <h1 class="mb-1">Salary Structure Details</h1>
                <p class="text-muted mb-0">View salary structure information.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="pill-badge {{ $structure->is_active ? 'pill-success' : 'pill-secondary' }}">{{ $structure->is_active ? 'Active' : 'Inactive' }}</span>
                <a href="{{ route('hr.payroll.salary-structures.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('hr.payroll.salary-structures.edit', $structure->id) }}" class="btn btn-settings-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Salary Structure Information</h5>
                            <p class="text-muted small mb-0">Components, deductions, and dates.</p>
                        </div>
                        <span class="pill-badge pill-secondary">Ref #{{ $structure->id }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Staff Member</label>
                                <div class="fw-semibold">{{ $structure->staff->name }}</div>
                                <div class="small text-muted">{{ $structure->staff->department->name ?? '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Status</label>
                                <div>
                                    <span class="pill-badge {{ $structure->is_active ? 'pill-success' : 'pill-secondary' }}">
                                        {{ $structure->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <h6 class="mb-3">Salary Components</h6>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Basic Salary</label>
                                <div class="h5 text-primary mb-0">Ksh {{ number_format($structure->basic_salary, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Housing Allowance</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->housing_allowance, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Transport Allowance</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->transport_allowance, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Medical Allowance</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->medical_allowance, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Other Allowances</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->other_allowances, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Gross Salary</label>
                                <div class="h4 text-success mb-0">Ksh {{ number_format($structure->gross_salary, 2) }}</div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <h6 class="mb-3">Deductions</h6>
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2">
                                <label class="text-muted small">NSSF</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->nssf_deduction, 2) }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="text-muted small">NHIF</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->nhif_deduction, 2) }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="text-muted small">PAYE</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->paye_deduction, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Other Deductions</label>
                                <div class="h6 mb-0">Ksh {{ number_format($structure->other_deductions, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="text-muted small">Total Deductions</label>
                                <div class="h5 text-danger mb-0">Ksh {{ number_format($structure->total_deductions, 2) }}</div>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="text-muted small">Net Salary</label>
                                <div class="h3 text-primary mb-0">Ksh {{ number_format($structure->net_salary, 2) }}</div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Effective From</label>
                                <div>{{ $structure->effective_from->format('F d, Y') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Effective To</label>
                                <div>{{ $structure->effective_to ? $structure->effective_to->format('F d, Y') : 'Ongoing' }}</div>
                            </div>
                            @if($structure->notes)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Notes</label>
                                <div class="alert alert-soft border-0 mb-0">{{ nl2br(e($structure->notes)) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


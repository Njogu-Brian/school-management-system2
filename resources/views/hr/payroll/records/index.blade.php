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
                <h1 class="mb-1">Payroll Records</h1>
                <p class="text-muted mb-0">View and manage payroll records.</p>
            </div>
            @if($records->total())
                <span class="pill-badge pill-secondary">{{ $records->total() }} records</span>
            @endif
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="mb-0 text-muted small">Narrow down records by period and status.</p>
                </div>
                <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-ghost-strong btn-sm">
                    <i class="bi bi-calendar3"></i> Manage Periods
                </a>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Payroll Period</label>
                        <select name="payroll_period_id" class="form-select">
                            <option value="">All Periods</option>
                            @foreach($periods as $p)
                                <option value="{{ $p->id }}" @selected(request('payroll_period_id')==$p->id)>{{ $p->period_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" @selected(request('status')==='draft')>Draft</option>
                            <option value="approved" @selected(request('status')==='approved')>Approved</option>
                            <option value="paid" @selected(request('status')==='paid')>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">All Payroll Records</h5>
                    <p class="mb-0 text-muted small">Periodized payroll outcomes for every staff member.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($records->total())
                        <span class="input-chip">{{ $records->total() }} total</span>
                    @endif
                    <span class="pill-badge pill-info">Payslip available</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Staff</th>
                                <th>Basic Salary</th>
                                <th>Gross Salary</th>
                                <th>Total Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $record->payrollPeriod->period_name }}</div>
                                        <div class="small text-muted">{{ $record->payrollPeriod->pay_date->format('M d, Y') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $record->staff->name }}</div>
                                        <div class="small text-muted">{{ $record->staff->staff_id }}</div>
                                    </td>
                                    <td>Ksh {{ number_format($record->basic_salary, 2) }}</td>
                                    <td><strong class="text-success">Ksh {{ number_format($record->gross_salary, 2) }}</strong></td>
                                    <td><span class="text-danger">Ksh {{ number_format($record->total_deductions, 2) }}</span></td>
                                    <td><strong class="text-primary">Ksh {{ number_format($record->net_salary, 2) }}</strong></td>
                                    <td>
                                        <span class="pill-badge {{ $record->status === 'approved' ? 'pill-success' : ($record->status === 'paid' ? 'pill-info' : 'pill-warning') }}">
                                            {{ ucfirst($record->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.payroll.records.show', $record->id) }}" class="btn btn-sm btn-ghost-strong">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="{{ route('hr.payroll.records.payslip', $record->id) }}" class="btn btn-sm btn-ghost-strong" target="_blank">
                                                <i class="bi bi-file-earmark-pdf"></i> Payslip
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No payroll records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($records->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $records->firstItem() }}–{{ $records->lastItem() }} of {{ $records->total() }} records
                    </div>
                    {{ $records->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


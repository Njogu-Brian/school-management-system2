@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payroll Periods</div>
                <h1 class="mb-1">Payroll Period: {{ $period->period_name }}</h1>
                <p class="text-muted mb-0">View and manage payroll period.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @php
                    $badgeColors = [
                        'draft' => 'pill-secondary',
                        'processing' => 'pill-warning',
                        'completed' => 'pill-success',
                        'locked' => 'pill-danger'
                    ];
                    $badge = $badgeColors[$period->status] ?? 'pill-secondary';
                @endphp
                <span class="pill-badge {{ $badge }}">{{ ucfirst($period->status) }}</span>
                <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Periods
                </a>
                @if($period->status === 'draft')
                    <form action="{{ route('hr.payroll.periods.process', $period->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Process payroll for this period? This will generate payroll records for all active staff.')">
                        @csrf
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-play-circle"></i> Process Payroll
                        </button>
                    </form>
                @endif
                @if($period->status === 'completed')
                    <form action="{{ route('hr.payroll.periods.lock', $period->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Lock this payroll period? Locked periods cannot be modified.')">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-lock"></i> Lock Period
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        <div class="row mb-3 g-3">
            <div class="col-md-3">
                <div class="settings-card stat-card primary">
                    <div class="card-body">
                        <p class="mb-1 text-muted small">Total Staff</p>
                        <h3 class="mb-0">{{ $period->staff_count ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card stat-card success">
                    <div class="card-body">
                        <p class="mb-1 text-muted small">Total Gross</p>
                        <h3 class="mb-0">Ksh {{ number_format($period->total_gross ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card stat-card danger">
                    <div class="card-body">
                        <p class="mb-1 text-muted small">Total Deductions</p>
                        <h3 class="mb-0">Ksh {{ number_format($period->total_deductions ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card stat-card info">
                    <div class="card-body">
                        <p class="mb-1 text-muted small">Total Net</p>
                        <h3 class="mb-0">Ksh {{ number_format($period->total_net ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Period Information</h5>
                    <p class="text-muted small mb-0">Key dates and status for this run.</p>
                </div>
                <span class="pill-badge pill-secondary">Ref #{{ $period->id }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Period Name</label>
                        <div class="fw-semibold">{{ $period->period_name }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Status</label>
                        <div><span class="pill-badge {{ $badge }}">{{ ucfirst($period->status) }}</span></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Start Date</label>
                        <div>{{ $period->start_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">End Date</label>
                        <div>{{ $period->end_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Pay Date</label>
                        <div>{{ $period->pay_date->format('F d, Y') }}</div>
                    </div>
                    @if($period->processed_at)
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Processed At</label>
                        <div>{{ $period->processed_at->format('F d, Y H:i') }}</div>
                        @if($period->processedBy)
                            <div class="small text-muted">By: {{ $period->processedBy->name }}</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($period->payrollRecords->count() > 0)
        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Payroll Records ({{ $period->payrollRecords->count() }})</h5>
                    <p class="text-muted small mb-0">Snapshot of generated records</p>
                </div>
                <span class="pill-badge pill-info">Read-only</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Gross Salary</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($period->payrollRecords as $record)
                                <tr>
                                    <td>{{ $record->staff->name }}</td>
                                    <td>Ksh {{ number_format($record->gross_salary, 2) }}</td>
                                    <td>Ksh {{ number_format($record->total_deductions, 2) }}</td>
                                    <td><strong>Ksh {{ number_format($record->net_salary, 2) }}</strong></td>
                                    <td><span class="pill-badge {{ $record->status === 'approved' ? 'pill-success' : 'pill-warning' }}">{{ ucfirst($record->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('hr.payroll.records.show', $record->id) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection


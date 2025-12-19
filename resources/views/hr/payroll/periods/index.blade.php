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
                <h1 class="mb-1">Payroll Periods</h1>
                <p class="text-muted mb-0">Manage payroll processing windows and status.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hr.payroll.periods.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Period
                </a>
                @if($periods->total())
                    <span class="pill-badge pill-secondary">{{ $periods->total() }} periods</span>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">All Payroll Periods</h5>
                    <p class="mb-0 text-muted small">Track period dates, pay dates, and completion.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($periods->total())
                        <span class="input-chip">{{ $periods->total() }} total</span>
                    @endif
                    <span class="pill-badge pill-info">Drafts are editable</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Year/Month</th>
                                <th>Period Dates</th>
                                <th>Pay Date</th>
                                <th>Staff Count</th>
                                <th>Total Net</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods as $period)
                                <tr>
                                    <td class="fw-semibold">{{ $period->period_name }}</td>
                                    <td>
                                        <div>{{ $period->year }}</div>
                                        <div class="small text-muted">Month {{ $period->month }}</div>
                                    </td>
                                    <td>{{ $period->start_date->format('M d') }} - {{ $period->end_date->format('M d, Y') }}</td>
                                    <td>{{ $period->pay_date->format('M d, Y') }}</td>
                                    <td><span class="pill-badge pill-info">{{ $period->staff_count ?? 0 }}</span></td>
                                    <td><strong>Ksh {{ number_format($period->total_net ?? 0, 2) }}</strong></td>
                                    <td>
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
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.payroll.periods.show', $period->id) }}" class="btn btn-sm btn-ghost-strong">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            @if($period->status === 'draft')
                                                <form action="{{ route('hr.payroll.periods.process', $period->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Process payroll for this period?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-settings-primary">
                                                        <i class="bi bi-play-circle"></i> Process
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No payroll periods found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($periods->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $periods->firstItem() }}–{{ $periods->lastItem() }} of {{ $periods->total() }} periods
                    </div>
                    {{ $periods->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


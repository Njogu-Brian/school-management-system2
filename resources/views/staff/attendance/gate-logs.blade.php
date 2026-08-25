@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
@php
    $reportRoute = $reportRoute ?? 'staff.attendance.report';
    $gateLogsRoute = $gateLogsRoute ?? 'staff.attendance.gate-logs';
    $roleLabels = [
        'check_in' => ['Daily check-in', 'pill-success'],
        'check_out' => ['Daily check-out', 'pill-danger'],
        'check_in_only' => ['Only punch (no checkout yet)', 'pill-warning'],
        'extra' => ['Extra punch (log only)', 'pill-secondary'],
    ];
@endphp
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Gate Punch Log</h1>
                <p class="text-muted mb-0">Every K40/BioTime punch. The <strong>first</strong> punch of the day sets check-in and the <strong>last</strong> sets check-out on the daily attendance report; any punches in between are logged here only.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route($indexReportRoute ?? $reportRoute) }}" class="btn btn-ghost-strong">
                    <i class="bi bi-graph-up"></i> Daily Report
                </a>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="multiple_only" id="multiple_only" value="1" @checked($multipleOnly)>
                            <label class="form-check-label" for="multiple_only">Days with more than one punch</label>
                        </div>
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
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-fingerprint"></i> All Gate Punches</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Staff</th>
                                <th>PIN</th>
                                <th>Device</th>
                                <th>Attendance use</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($punches as $punch)
                                @php
                                    $role = $roleMap[$punch->id] ?? 'extra';
                                    [$roleText, $roleClass] = $roleLabels[$role] ?? ['Extra punch', 'pill-secondary'];
                                @endphp
                                <tr>
                                    <td>{{ $punch->punch_time?->format('d M Y H:i:s') }}</td>
                                    <td>
                                        @if($punch->staff)
                                            <div class="fw-semibold">{{ $punch->staff->full_name }}</div>
                                            <small class="text-muted">{{ $punch->staff->staff_id }}</small>
                                        @else
                                            <span class="text-warning">Unmatched (PIN {{ $punch->emp_code }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $punch->emp_code }}</td>
                                    <td>{{ $punch->terminal_alias ?: ($punch->terminal_sn ?: '—') }}</td>
                                    <td><span class="pill-badge {{ $roleClass }}">{{ $roleText }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No gate punches found for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($punches->hasPages())
                <div class="card-footer">
                    {{ $punches->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">My Account / Attendance</div>
                <h1 class="mb-1">My Attendance Report</h1>
                <p class="text-muted mb-0">Sign-in/sign-out history for {{ $staffName }}.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Total</div>
                        <h3 class="mb-0">{{ $summary['total'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-success h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Present</div>
                        <h3 class="mb-0">{{ $summary['present'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Late</div>
                        <h3 class="mb-0">{{ $summary['late'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase fw-semibold small mb-1">Absent</div>
                        <h3 class="mb-0">{{ $summary['absent'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Date Range</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Sign-in / Sign-out Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Check In Distance</th>
                                <th>Check Out</th>
                                <th>Check Out Distance</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendance as $record)
                                <tr>
                                    <td>{{ $record->date->format('d M Y') }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $record->status)) }}</td>
                                    <td>{{ $record->check_in_time ? $record->check_in_time->format('H:i') : '—' }}</td>
                                    <td>{{ $record->check_in_distance_meters !== null ? number_format($record->check_in_distance_meters, 1).' m' : '—' }}</td>
                                    <td>{{ $record->check_out_time ? $record->check_out_time->format('H:i') : '—' }}</td>
                                    <td>{{ $record->check_out_distance_meters !== null ? number_format($record->check_out_distance_meters, 1).' m' : '—' }}</td>
                                    <td>{{ $record->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No attendance records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($attendance->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing {{ $attendance->firstItem() }}–{{ $attendance->lastItem() }} of {{ $attendance->total() }}
                    </div>
                    {{ $attendance->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

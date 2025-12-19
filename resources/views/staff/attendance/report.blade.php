@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Staff Attendance Report</h1>
                <p class="text-muted mb-0">View attendance history and statistics.</p>
            </div>
            <a href="{{ route('staff.attendance.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Marking
            </a>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Filter by staff and date range.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
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
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Attendance Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendance as $record)
                                <tr>
                                    <td>{{ $record->date->format('d M Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $record->staff->full_name }}</div>
                                        <small class="text-muted">{{ $record->staff->staff_id }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'present' => 'pill-success',
                                                'absent' => 'pill-danger',
                                                'late' => 'pill-warning',
                                                'half_day' => 'pill-info'
                                            ];
                                        @endphp
                                        <span class="pill-badge {{ $statusColors[$record->status] ?? 'pill-secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $record->check_in_time ? $record->check_in_time->format('H:i') : '—' }}</td>
                                    <td>{{ $record->check_out_time ? $record->check_out_time->format('H:i') : '—' }}</td>
                                    <td>{{ $record->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No attendance records found.</td>
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


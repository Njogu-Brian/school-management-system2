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
                <h1 class="mb-1">Staff Attendance</h1>
                <p class="text-muted mb-0">Mark and view staff attendance.</p>
            </div>
            <a href="{{ route('staff.attendance.report') }}" class="btn btn-ghost-strong">
                <i class="bi bi-graph-up"></i> View Reports
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Total</div>
                                <h3 class="mb-0">{{ $summary['total'] }}</h3>
                            </div>
                            <span class="pill-icon pill-primary"><i class="bi bi-people"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Present</div>
                                <h3 class="mb-0">{{ $summary['present'] }}</h3>
                            </div>
                            <span class="pill-icon pill-success"><i class="bi bi-check-circle"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Absent</div>
                                <h3 class="mb-0">{{ $summary['absent'] }}</h3>
                            </div>
                            <span class="pill-icon pill-danger"><i class="bi bi-x-circle"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Late</div>
                                <h3 class="mb-0">{{ $summary['late'] }}</h3>
                            </div>
                            <span class="pill-icon pill-warning"><i class="bi bi-clock"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Pick date and staff.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}" required>
                    </div>
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
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Attendance for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.attendance.bulk-mark') }}" method="POST" id="bulkAttendanceForm">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    
                    <div class="table-responsive">
                        <table class="table table-modern table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Staff</th>
                                    <th>Status</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $staffList = $allStaff ?? $staff;
                                @endphp
                                @foreach($staffList as $s)
                                    @php
                                        $attendanceRecord = isset($attendanceRecords) ? $attendanceRecords->firstWhere('staff_id', $s->id) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $s->full_name }}</div>
                                            <small class="text-muted">{{ $s->staff_id }}</small>
                                        </td>
                                        <td>
                                            <input type="hidden" name="attendance[{{ $loop->index }}][staff_id]" value="{{ $s->id }}">
                                            <select name="attendance[{{ $loop->index }}][status]" class="form-select form-select-sm" required>
                                                <option value="present" @selected($attendanceRecord?->status === 'present')>Present</option>
                                                <option value="absent" @selected($attendanceRecord?->status === 'absent')>Absent</option>
                                                <option value="late" @selected($attendanceRecord?->status === 'late')>Late</option>
                                                <option value="half_day" @selected($attendanceRecord?->status === 'half_day')>Half Day</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="attendance[{{ $loop->index }}][check_in_time]" class="form-control form-control-sm" value="{{ $attendanceRecord?->check_in_time?->format('H:i') }}">
                                        </td>
                                        <td>
                                            <input type="time" name="attendance[{{ $loop->index }}][check_out_time]" class="form-control form-control-sm" value="{{ $attendanceRecord?->check_out_time?->format('H:i') }}">
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[{{ $loop->index }}][notes]" class="form-control form-control-sm" value="{{ $attendanceRecord?->notes }}" placeholder="Optional notes">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-save"></i> Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


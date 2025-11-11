@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Staff Attendance</h2>
            <small class="text-muted">Mark and view staff attendance</small>
        </div>
        <a href="{{ route('staff.attendance.report') }}" class="btn btn-outline-primary">
            <i class="bi bi-graph-up"></i> View Reports
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Total</h6>
                            <h3 class="mb-0">{{ $summary['total'] }}</h3>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Present</h6>
                            <h3 class="mb-0">{{ $summary['present'] }}</h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Absent</h6>
                            <h3 class="mb-0">{{ $summary['absent'] }}</h3>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Late</h6>
                            <h3 class="mb-0">{{ $summary['late'] }}</h3>
                        </div>
                        <i class="bi bi-clock fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-3">
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
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Attendance for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('staff.attendance.bulk-mark') }}" method="POST" id="bulkAttendanceForm">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                
                <div class="table-responsive">
                    <table class="table table-hover">
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
                            @foreach($staff as $s)
                                @php
                                    $attendance = $attendance->firstWhere('staff_id', $s->id);
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


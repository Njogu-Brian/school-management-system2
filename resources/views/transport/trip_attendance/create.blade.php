@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb"><a href="{{ route('transport.dashboard') }}">Transport</a> / Attendance</div>
                <h1>Trip Attendance Checklist</h1>
                <p>{{ $trip->trip_name ?? $trip->name }} · {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.trip-attendance.index', ['trip' => $trip->id, 'date' => $selected_date]) }}" class="btn btn-ghost-strong">View History</a>
                <a href="{{ route('transport.dashboard') }}" class="btn btn-ghost-strong">Back to Transport</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        {{-- Trip Info --}}
        <div class="settings-card mt-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Vehicle</div>
                        <div class="fw-semibold">{{ $trip->vehicle->vehicle_number ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Driver</div>
                        <div class="fw-semibold">
                            @if($trip->driver)
                                {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Total Students</div>
                        <div class="fw-semibold">{{ $students->count() }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Date</div>
                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($selected_date)->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Form --}}
        <form method="POST" action="{{ route('transport.trip-attendance.store', $trip) }}">
            @csrf
            <input type="hidden" name="date" value="{{ $selected_date }}">

            @if($students_by_class->count() > 0)
                @foreach($students_by_class as $className => $classStudents)
                    <div class="settings-card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">{{ $className }} ({{ $classStudents->count() }} student(s))</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student Name</th>
                                            <th>Admission #</th>
                                            <th>Status</th>
                                            <th>Boarded At</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($classStudents as $index => $student)
                                            @php
                                                $existing = $attendance_records->get($student->id);
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></td>
                                                <td>{{ $student->admission_number }}</td>
                                                <td>
                                                    <input type="hidden" name="attendance[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                                    <select name="attendance[{{ $student->id }}][status]" class="form-select form-select-sm" required>
                                                        <option value="present" @selected(($existing && $existing->status === 'present') || !$existing)>Present</option>
                                                        <option value="absent" @selected($existing && $existing->status === 'absent')>Absent</option>
                                                        <option value="late" @selected($existing && $existing->status === 'late')>Late</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="time" 
                                                           name="attendance[{{ $student->id }}][boarded_at]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ $existing && $existing->boarded_at ? \Carbon\Carbon::parse($existing->boarded_at)->format('H:i') : '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="attendance[{{ $student->id }}][notes]" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ $existing->notes ?? '' }}" 
                                                           placeholder="Optional notes">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="settings-card mt-3">
                    <div class="card-body">
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Save Attendance
                        </button>
                        <a href="{{ route('transport.dashboard') }}" class="btn btn-ghost-strong">Cancel</a>
                    </div>
                </div>
            @else
                <div class="settings-card mt-3">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <h5 class="mt-3">No Students Assigned</h5>
                        <p class="text-muted mb-0">No students are assigned to this trip for {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}.</p>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection



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
                <h1>Attendance History</h1>
                <p>{{ $trip->trip_name ?? $trip->name }} · {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <form method="GET" action="{{ route('transport.trip-attendance.index', $trip) }}" class="d-flex gap-2">
                    <input type="date" name="date" value="{{ $selected_date }}" class="form-control" onchange="this.form.submit()">
                </form>
                <a href="{{ route('transport.trip-attendance.create', ['trip' => $trip->id, 'date' => $selected_date]) }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Take Attendance
                </a>
                <a href="{{ route('transport.dashboard') }}" class="btn btn-ghost-strong">Back to Transport</a>
            </div>
        </div>

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
                        <div class="text-muted small">Date</div>
                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($selected_date)->format('M d, Y') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Records</div>
                        <div class="fw-semibold">{{ $attendance->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Records --}}
        @if($attendance->count() > 0)
            <div class="settings-card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Attendance Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Admission #</th>
                                    <th>Status</th>
                                    <th>Boarded At</th>
                                    <th>Notes</th>
                                    <th>Marked By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendance as $index => $record)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>{{ $record->student->first_name }} {{ $record->student->last_name }}</strong></td>
                                        <td>{{ $record->student->classroom->name ?? '—' }}</td>
                                        <td>{{ $record->student->admission_number }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'present' => 'success',
                                                    'absent' => 'danger',
                                                    'late' => 'warning'
                                                ];
                                                $color = $statusColors[$record->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst($record->status) }}</span>
                                        </td>
                                        <td>
                                            @if($record->boarded_at)
                                                {{ \Carbon\Carbon::parse($record->boarded_at)->format('H:i') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $record->notes ?? '—' }}</td>
                                        <td>
                                            @if($record->marker)
                                                {{ $record->marker->name }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="settings-card mt-3">
                <div class="card-body text-center py-5">
                    <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                    <h5 class="mt-3">No Attendance Records</h5>
                    <p class="text-muted mb-0">No attendance has been recorded for this trip on {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}.</p>
                    <a href="{{ route('transport.trip-attendance.create', ['trip' => $trip->id, 'date' => $selected_date]) }}" class="btn btn-settings-primary mt-3">
                        Take Attendance
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection



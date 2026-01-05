@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb"><a href="{{ route('driver.index') }}">Driver</a> / Trip</div>
                <h1>{{ $trip->trip_name ?? $trip->name }}</h1>
                <p>
                    @if($trip->direction) {{ ucfirst($trip->direction) }} · @endif
                    @if($trip->vehicle) {{ $trip->vehicle->vehicle_number }} · @endif
                    {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.trip-attendance.create', ['trip' => $trip->id, 'date' => $selected_date]) }}" class="btn btn-settings-primary">
                    <i class="bi bi-clipboard-check"></i> Take Attendance
                </a>
                <a href="{{ route('driver.index', ['date' => $selected_date]) }}" class="btn btn-ghost-strong">Back to Trips</a>
            </div>
        </div>

        {{-- Trip Information --}}
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Trip Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Vehicle</div>
                        <div class="fw-semibold">{{ $trip->vehicle->vehicle_number ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Direction</div>
                        <div class="fw-semibold">
                            @if($trip->direction)
                                <span class="badge bg-info">{{ ucfirst($trip->direction) }}</span>
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
                        <div class="text-muted small">Stops</div>
                        <div class="fw-semibold">{{ $trip->stops->count() }}</div>
                    </div>
                </div>

                @if($trip->stops->count() > 0)
                    <div class="mt-4">
                        <div class="text-muted small mb-2">Route Stops</div>
                        <div class="list-group">
                            @foreach($trip->stops as $stop)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>#{{ $stop->sequence_order }}</strong> {{ $stop->dropOffPoint->name ?? '—' }}
                                        </div>
                                        @if($stop->estimated_time)
                                            <span class="badge bg-light text-dark">{{ \Carbon\Carbon::parse($stop->estimated_time)->format('H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Students by Class --}}
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
                                        <th>Drop-Off Point</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($classStudents as $index => $student)
                                        @php
                                            // Find assignment that matches this trip (morning or evening)
                                            $assignment = $student->assignments->first(function($ass) use ($trip) {
                                                return $ass->morning_trip_id == $trip->id || $ass->evening_trip_id == $trip->id;
                                            });
                                            $dropOffPoint = null;
                                            if($assignment) {
                                                $dropOffPoint = ($trip->direction === 'pickup' || $assignment->morning_trip_id == $trip->id)
                                                    ? $assignment->morningDropOffPoint 
                                                    : $assignment->eveningDropOffPoint;
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></td>
                                            <td>{{ $student->admission_number }}</td>
                                            <td>{{ $dropOffPoint->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="settings-card mt-3">
                <div class="card-body text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <h5 class="mt-3">No Students Assigned</h5>
                    <p class="text-muted mb-0">No students are assigned to this trip for {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection


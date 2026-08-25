@extends('layouts.app')

@push('styles')
    @include('transport.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport / Trips</p>
                <h1 class="mb-1">{{ $trip->trip_name }}</h1>
                <p class="text-muted mb-0">
                    Vehicle: {{ $trip->vehicle->vehicle_number ?? 'N/A' }}
                    · {{ $trip->type ?: ($trip->direction ? ucfirst($trip->direction) : 'Trip') }}
                    · Students and their pickup / drop-off points
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-settings-primary">
                    <i class="bi bi-people"></i> Assign students
                </a>
                <a href="{{ route('transport.trips.edit', $trip) }}" class="btn btn-ghost-strong">
                    <i class="bi bi-pencil"></i> Edit trip
                </a>
                <a href="{{ route('transport.trips.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> All trips
                </a>
            </div>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

        <div class="transport-stats">
            <div class="transport-stat">
                <div class="label">Students on this trip</div>
                <div class="value">{{ $assigned->count() }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Morning leg</div>
                <div class="value">{{ (int) ($morningOnThisTrip ?? 0) }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Evening leg</div>
                <div class="value">{{ (int) ($eveningOnThisTrip ?? 0) }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Stops</div>
                <div class="value">{{ ($stopCounts ?? collect())->count() }}</div>
            </div>
        </div>

        @if(($stopCounts ?? collect())->isNotEmpty())
            <div class="settings-card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ $stopLegLabel ?? 'Stops' }}</h5></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @foreach($stopCounts as $stop => $count)
                        <span class="input-chip">{{ $stop }} · {{ $count }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assigned children</h5>
                <span class="input-chip">{{ $assigned->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Admission</th>
                                <th>Class</th>
                                <th>Morning pickup</th>
                                <th>Evening drop-off</th>
                                <th>On this trip as</th>
                                <th class="text-end">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assigned as $row)
                                @php
                                    $student = $row->student;
                                    $onMorning = (int) $row->morning_trip_id === (int) $trip->id;
                                    $onEvening = (int) $row->evening_trip_id === (int) $trip->id;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $student->full_name }}</td>
                                    <td>{{ $student->admission_number ?? '—' }}</td>
                                    <td>
                                        {{ optional($student->classroom)->name ?? '—' }}
                                        @if($student->stream)
                                            <small class="text-muted">/ {{ $student->stream->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ optional($row->morningDropOffPoint)->name ?? '—' }}</td>
                                    <td>{{ optional($row->eveningDropOffPoint)->name ?? '—' }}</td>
                                    <td>
                                        @if($onMorning)
                                            <span class="badge bg-primary">Morning</span>
                                        @endif
                                        @if($onEvening)
                                            <span class="badge bg-info">Evening</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('transport.student-assignments.index', ['tab' => 'student', 'student_id' => $student->id]) }}" class="btn btn-sm btn-ghost-strong">
                                            Assignments
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No students on this trip yet. Assign them from
                                        <a href="{{ route('transport.student-assignments.index') }}">Assignments</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('transport.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">{{ $dropOffPoint->name }}</h1>
                <p class="text-muted mb-0">
                    Students with morning pickup or evening drop-off at this stop.
                    @if($dropOffPoint->isOwnMeans())
                        <span class="badge bg-secondary">System</span>
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.dropoffpoints.edit', $dropOffPoint) }}" class="btn btn-settings-primary">
                    <i class="bi bi-pencil"></i> Edit rates
                </a>
                <a href="{{ route('transport.student-dropoffs.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-geo-alt"></i> Student drop-offs
                </a>
                <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> All points
                </a>
            </div>
        </div>

        <div class="transport-stats">
            <div class="transport-stat">
                <div class="label">Two-way / term</div>
                <div class="value">
                    {{ $dropOffPoint->two_way_amount !== null ? number_format((float) $dropOffPoint->two_way_amount, 2) : '—' }}
                </div>
            </div>
            <div class="transport-stat">
                <div class="label">One-way / term</div>
                <div class="value">
                    {{ $dropOffPoint->one_way_amount !== null ? number_format((float) $dropOffPoint->one_way_amount, 2) : '—' }}
                </div>
            </div>
            <div class="transport-stat">
                <div class="label">Students at this stop</div>
                <div class="value">{{ $assignments->count() }}</div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assigned children</h5>
                <span class="input-chip">{{ $assignments->count() }}</span>
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
                                <th>Morning trip</th>
                                <th>Evening trip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $row)
                                @php
                                    $student = $row->student;
                                    $morningHere = (int) $row->morning_drop_off_point_id === (int) $dropOffPoint->id;
                                    $eveningHere = (int) $row->evening_drop_off_point_id === (int) $dropOffPoint->id;
                                    $morningVehicle = optional($row->morningTrip?->vehicle)->vehicle_number;
                                    $eveningVehicle = optional($row->eveningTrip?->vehicle)->vehicle_number;
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
                                    <td>
                                        @if($morningHere)
                                            <span class="badge bg-primary">Here</span>
                                        @else
                                            <span class="text-muted">{{ optional($row->morningDropOffPoint)->name ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($eveningHere)
                                            <span class="badge bg-info">Here</span>
                                        @else
                                            <span class="text-muted">{{ optional($row->eveningDropOffPoint)->name ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->morningTrip)
                                            <a href="{{ route('transport.trips.assign', $row->morningTrip) }}">
                                                {{ $morningVehicle ?? $row->morningTrip->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->eveningTrip)
                                            <a href="{{ route('transport.trips.assign', $row->eveningTrip) }}">
                                                {{ $eveningVehicle ?? $row->eveningTrip->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No students assigned to this stop yet.</td>
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

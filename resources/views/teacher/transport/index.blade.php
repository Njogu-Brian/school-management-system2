@extends('layouts.app')

@push('styles')
    @include('transport.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div>
                <p class="eyebrow mb-1">Transport</p>
                <h1 class="mb-1">School Transport</h1>
                <p class="mb-0">View transport information for students in your assigned classes.</p>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-funnel me-1"></i> Filters</h5></div>
            <div class="card-body">
                <form method="GET" class="transport-toolbar">
                    <div style="min-width:220px; flex:1;">
                        <label class="form-label">Classroom</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-settings-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-bus-front me-1"></i> Students using transport</h5>
                <span class="input-chip">{{ $students->total() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Morning Trip</th>
                                <th>Morning Drop-off</th>
                                <th>Evening Trip</th>
                                <th>Evening Drop-off</th>
                                <th>Vehicle</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php $assignment = $student->assignments->first(); @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $student->full_name }}</div>
                                        <small class="text-muted">{{ $student->admission_number }}</small>
                                    </td>
                                    <td>{{ $student->classroom->name ?? '—' }}</td>
                                    <td>
                                        @if($assignment?->morningTrip)
                                            <span class="pill-badge pill-primary">{{ $assignment->morningTrip->name }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment?->morningDropOffPoint?->name ?? '—' }}</td>
                                    <td>
                                        @if($assignment?->eveningTrip)
                                            <span class="pill-badge pill-info">{{ $assignment->eveningTrip->name }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment?->eveningDropOffPoint?->name ?? '—' }}</td>
                                    <td>
                                        @php
                                            $vehicle = $student->vehicle
                                                ?? $assignment?->morningTrip?->vehicle
                                                ?? $assignment?->eveningTrip?->vehicle;
                                        @endphp
                                        @if($vehicle)
                                            <span class="input-chip">{{ $vehicle->vehicle_number ?? $vehicle->registration_number ?? '—' }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('teacher.transport.show', $student) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No students using transport found in your assigned classes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($students->hasPages())
                <div class="card-body border-top">
                    {{ $students->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('transport.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div>
                <p class="eyebrow mb-1">Transport / Assignments</p>
                <h1 class="mb-1">Assignment Details</h1>
                <p class="mb-0">{{ $assignment->student?->full_name ?? 'Student' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('transport.student-assignments.index', ['tab' => 'student', 'student_id' => $assignment->student_id]) }}" class="btn btn-settings-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="transport-stats">
            <div class="transport-stat">
                <div class="label">Morning pickup</div>
                <div class="value" style="font-size:1rem;">{{ $assignment->morningDropOffPoint->name ?? '—' }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Evening drop-off</div>
                <div class="value" style="font-size:1rem;">{{ $assignment->eveningDropOffPoint->name ?? '—' }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Morning trip</div>
                <div class="value" style="font-size:1rem;">{{ $assignment->morningTrip->name ?? '—' }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Evening trip</div>
                <div class="value" style="font-size:1rem;">{{ $assignment->eveningTrip->name ?? '—' }}</div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header"><h5 class="mb-0">Student</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Name</div>
                        <div class="fw-semibold">{{ $assignment->student?->full_name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Admission</div>
                        <div class="fw-semibold">{{ $assignment->student?->admission_number ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Class</div>
                        <div class="fw-semibold">{{ optional($assignment->student?->classroom)->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

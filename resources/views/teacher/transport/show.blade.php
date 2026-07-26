@extends('layouts.app')

@push('styles')
    @include('transport.partials.styles')
@endpush

@section('content')
@php $assignment = $student->assignments->first(); @endphp
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div>
                <p class="eyebrow mb-1">Transport</p>
                <h1 class="mb-1">{{ $student->full_name }}</h1>
                <p class="mb-0">Transport details · {{ $student->admission_number }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('teacher.transport.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="transport-stats">
            <div class="transport-stat">
                <div class="label">Class</div>
                <div class="value" style="font-size:1rem;">{{ $student->classroom->name ?? '—' }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Stream</div>
                <div class="value" style="font-size:1rem;">{{ $student->stream->name ?? '—' }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Admission</div>
                <div class="value" style="font-size:1rem;">{{ $student->admission_number }}</div>
            </div>
            <div class="transport-stat">
                <div class="label">Route</div>
                <div class="value" style="font-size:1rem;">{{ $student->route->name ?? '—' }}</div>
            </div>
        </div>

        <div class="transport-card mb-3">
            <div class="transport-split">
                <section class="transport-split-pane">
                    <div class="transport-pane-label"><strong>Morning pickup</strong></div>
                    @if($assignment?->morningTrip || $assignment?->morningDropOffPoint)
                        <div class="transport-tile transport-tile-morning">
                            <div>
                                <p class="transport-tile-name">{{ $assignment->morningTrip->name ?? 'No trip' }}</p>
                                <div class="text-muted small mt-1">{{ $assignment->morningDropOffPoint->name ?? 'No stop' }}</div>
                                @if($assignment->morningTrip?->vehicle)
                                    <span class="input-chip mt-2">
                                        {{ $assignment->morningTrip->vehicle->vehicle_number ?? $assignment->morningTrip->vehicle->registration_number }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="transport-empty">No morning assignment.</div>
                    @endif
                </section>
                <section class="transport-split-pane">
                    <div class="transport-pane-label"><strong>Evening drop-off</strong></div>
                    @if($assignment?->eveningTrip || $assignment?->eveningDropOffPoint)
                        <div class="transport-tile transport-tile-evening">
                            <div>
                                <p class="transport-tile-name">{{ $assignment->eveningTrip->name ?? 'No trip' }}</p>
                                <div class="text-muted small mt-1">{{ $assignment->eveningDropOffPoint->name ?? 'No stop' }}</div>
                                @if($assignment->eveningTrip?->vehicle)
                                    <span class="input-chip mt-2">
                                        {{ $assignment->eveningTrip->vehicle->vehicle_number ?? $assignment->eveningTrip->vehicle->registration_number }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="transport-empty">No evening assignment.</div>
                    @endif
                </section>
            </div>
        </div>

        @if(!$assignment && !$student->route)
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i> No transport assignment found for this student.
            </div>
        @endif
    </div>
</div>
@endsection

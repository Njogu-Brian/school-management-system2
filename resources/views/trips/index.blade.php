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
                <h1 class="mb-1">Trips</h1>
                <p class="mb-0">Morning pickup and evening drop-off paired by vehicle.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('transport.trips.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Create Trip
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Vehicles &amp; trips</h5>
                    <small class="text-muted">Student counts are live assignments on each trip.</small>
                </div>
                <span class="input-chip">{{ $trips->count() }} trip(s) · {{ $groups->count() }} vehicle(s)</span>
            </div>
        </div>

        <div class="transport-board">
            @forelse ($groups as $group)
                @php
                    $morningTrips = $group['morning'];
                    $eveningTrips = $group['evening'];
                    $driver = $group['driver'];
                    $driverName = $driver
                        ? ($driver->user->name ?? trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')))
                        : null;
                @endphp
                <article class="transport-card">
                    <header class="transport-card-head">
                        <div>
                            <h2 class="transport-card-title">{{ $group['label'] }}</h2>
                            <div class="transport-card-meta">
                                <i class="bi bi-person"></i>
                                {{ $driverName ?: 'No driver assigned' }}
                            </div>
                        </div>
                        <div class="transport-card-stats">
                            <span class="pill-badge pill-primary">
                                <i class="bi bi-sunrise"></i> {{ $morningTrips->count() }} morning
                            </span>
                            <span class="pill-badge pill-info">
                                <i class="bi bi-sunset"></i> {{ $eveningTrips->count() }} evening
                            </span>
                            <span class="input-chip">
                                <i class="bi bi-people"></i>
                                {{ (int) $group['morning_students'] + (int) $group['evening_students'] }} students
                            </span>
                        </div>
                    </header>

                    <div class="transport-split">
                        <section class="transport-split-pane">
                            <div class="transport-pane-label">
                                <strong>Morning pickup</strong>
                                <span class="input-chip">{{ (int) $group['morning_students'] }} assigned</span>
                            </div>
                            @forelse ($morningTrips as $trip)
                                <div class="transport-tile transport-tile-morning">
                                    <div>
                                        <p class="transport-tile-name">{{ $trip->name }}</p>
                                        <span class="pill-badge pill-primary mt-1">
                                            <i class="bi bi-people-fill"></i>
                                            {{ (int) $trip->assigned_students_count }}
                                            {{ \Illuminate\Support\Str::plural('student', (int) $trip->assigned_students_count) }}
                                        </span>
                                    </div>
                                    <div class="transport-tile-actions">
                                        <a href="{{ route('transport.trips.assign', $trip) }}" class="btn btn-sm btn-settings-primary">
                                            <i class="bi bi-people"></i> Assign
                                        </a>
                                        <a href="{{ route('transport.trips.edit', $trip) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transport.trips.destroy', $trip) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this morning trip? Students on it will be unassigned from that leg.');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="transport-empty">No morning trips for this vehicle.</div>
                            @endforelse
                        </section>

                        <section class="transport-split-pane">
                            <div class="transport-pane-label">
                                <strong>Evening drop-off</strong>
                                <span class="input-chip">{{ (int) $group['evening_students'] }} assigned</span>
                            </div>
                            @forelse ($eveningTrips as $trip)
                                <div class="transport-tile transport-tile-evening">
                                    <div>
                                        <p class="transport-tile-name">{{ $trip->name }}</p>
                                        <span class="pill-badge pill-info mt-1">
                                            <i class="bi bi-people-fill"></i>
                                            {{ (int) $trip->assigned_students_count }}
                                            {{ \Illuminate\Support\Str::plural('student', (int) $trip->assigned_students_count) }}
                                        </span>
                                    </div>
                                    <div class="transport-tile-actions">
                                        <a href="{{ route('transport.trips.assign', $trip) }}" class="btn btn-sm btn-settings-primary">
                                            <i class="bi bi-people"></i> Assign
                                        </a>
                                        <a href="{{ route('transport.trips.edit', $trip) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transport.trips.destroy', $trip) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this evening trip? Students on it will be unassigned from that leg.');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="transport-empty">No evening trips for this vehicle.</div>
                            @endforelse
                        </section>
                    </div>
                </article>
            @empty
                <div class="settings-card">
                    <div class="card-body text-center text-muted py-5">No trips found.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

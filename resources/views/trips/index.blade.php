@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .trips-board {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .trip-vehicle-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .trip-vehicle-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.04), transparent);
        }
        .trip-vehicle-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.01em;
        }
        .trip-vehicle-meta {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.15rem;
        }
        .trip-vehicle-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .trip-legs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        @media (max-width: 900px) {
            .trip-legs { grid-template-columns: 1fr; }
        }
        .trip-leg {
            padding: 1rem 1.25rem 1.15rem;
        }
        .trip-leg + .trip-leg {
            border-left: 1px solid rgba(15, 23, 42, 0.06);
        }
        @media (max-width: 900px) {
            .trip-leg + .trip-leg {
                border-left: 0;
                border-top: 1px solid rgba(15, 23, 42, 0.06);
            }
        }
        .trip-leg-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .trip-leg-label strong {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }
        .trip-tile {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #f8fafc;
            margin-bottom: 0.65rem;
        }
        .trip-tile:last-child { margin-bottom: 0; }
        .trip-tile-morning {
            border-left: 3px solid #4f46e5;
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.06), #f8fafc 40%);
        }
        .trip-tile-evening {
            border-left: 3px solid #0ea5e9;
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.07), #f8fafc 40%);
        }
        .trip-tile-name {
            font-weight: 650;
            margin: 0;
            font-size: 0.98rem;
        }
        .trip-tile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        .trip-empty {
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 0.75rem 0.25rem;
        }
        .theme-dark .trip-vehicle-card {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: none;
        }
        .theme-dark .trip-vehicle-head,
        .theme-dark .trip-leg + .trip-leg {
            border-color: rgba(255, 255, 255, 0.08);
        }
        .theme-dark .trip-tile {
            background: #0f172a;
            border-color: rgba(255, 255, 255, 0.1);
        }
        .theme-dark .trip-tile-morning {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.18), #0f172a 45%);
        }
        .theme-dark .trip-tile-evening {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.16), #0f172a 45%);
        }
        .theme-dark .trip-vehicle-meta,
        .theme-dark .trip-leg-label strong,
        .theme-dark .trip-empty {
            color: #94a3b8;
        }
    </style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">Trips</h1>
                <p class="text-muted mb-0">Morning pickup and evening drop-off paired by vehicle.</p>
            </div>
            <div class="d-flex gap-2">
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

        <div class="trips-board">
            @forelse ($groups as $group)
                @php
                    $morningTrips = $group['morning'];
                    $eveningTrips = $group['evening'];
                    $driver = $group['driver'];
                    $driverName = $driver
                        ? ($driver->user->name ?? trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')))
                        : null;
                @endphp
                <article class="trip-vehicle-card">
                    <header class="trip-vehicle-head">
                        <div>
                            <h2 class="trip-vehicle-title">{{ $group['label'] }}</h2>
                            <div class="trip-vehicle-meta">
                                <i class="bi bi-person"></i>
                                {{ $driverName ?: 'No driver assigned' }}
                            </div>
                        </div>
                        <div class="trip-vehicle-stats">
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

                    <div class="trip-legs">
                        <section class="trip-leg">
                            <div class="trip-leg-label">
                                <strong>Morning pickup</strong>
                                <span class="input-chip">{{ (int) $group['morning_students'] }} assigned</span>
                            </div>
                            @forelse ($morningTrips as $trip)
                                <div class="trip-tile trip-tile-morning">
                                    <div>
                                        <p class="trip-tile-name">{{ $trip->name }}</p>
                                        <span class="pill-badge pill-primary mt-1">
                                            <i class="bi bi-people-fill"></i>
                                            {{ (int) $trip->assigned_students_count }}
                                            {{ \Illuminate\Support\Str::plural('student', (int) $trip->assigned_students_count) }}
                                        </span>
                                    </div>
                                    <div class="trip-tile-actions">
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
                                <div class="trip-empty">No morning trips for this vehicle.</div>
                            @endforelse
                        </section>

                        <section class="trip-leg">
                            <div class="trip-leg-label">
                                <strong>Evening drop-off</strong>
                                <span class="input-chip">{{ (int) $group['evening_students'] }} assigned</span>
                            </div>
                            @forelse ($eveningTrips as $trip)
                                <div class="trip-tile trip-tile-evening">
                                    <div>
                                        <p class="trip-tile-name">{{ $trip->name }}</p>
                                        <span class="pill-badge pill-info mt-1">
                                            <i class="bi bi-people-fill"></i>
                                            {{ (int) $trip->assigned_students_count }}
                                            {{ \Illuminate\Support\Str::plural('student', (int) $trip->assigned_students_count) }}
                                        </span>
                                    </div>
                                    <div class="trip-tile-actions">
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
                                <div class="trip-empty">No evening trips for this vehicle.</div>
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

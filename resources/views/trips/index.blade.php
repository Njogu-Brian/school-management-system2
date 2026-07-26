@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
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

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Vehicles &amp; trips</h5>
                <span class="input-chip">{{ $trips->count() }} trip(s) · {{ $groups->count() }} group(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Morning pickup</th>
                                <th>Evening drop-off</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $group)
                                @php
                                    $morningTrips = $group['morning'];
                                    $eveningTrips = $group['evening'];
                                    $driver = $group['driver'];
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $group['label'] }}</td>
                                    <td>
                                        @if($driver)
                                            {{ $driver->user->name ?? trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse ($morningTrips as $trip)
                                            <div class="mb-2 {{ !$loop->last ? 'pb-2 border-bottom' : '' }}">
                                                <div class="fw-semibold">{{ $trip->name }}</div>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    <a href="{{ route('transport.trips.assign', $trip) }}" class="btn btn-sm btn-settings-primary">
                                                        <i class="bi bi-people"></i> Assign
                                                    </a>
                                                    <a href="{{ route('transport.trips.edit', $trip) }}" class="btn btn-sm btn-ghost-strong">
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
                                            <span class="text-muted">No morning trip</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @forelse ($eveningTrips as $trip)
                                            <div class="mb-2 {{ !$loop->last ? 'pb-2 border-bottom' : '' }}">
                                                <div class="fw-semibold">{{ $trip->name }}</div>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    <a href="{{ route('transport.trips.assign', $trip) }}" class="btn btn-sm btn-settings-primary">
                                                        <i class="bi bi-people"></i> Assign
                                                    </a>
                                                    <a href="{{ route('transport.trips.edit', $trip) }}" class="btn btn-sm btn-ghost-strong">
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
                                            <span class="text-muted">No evening trip</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No trips found.</td>
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

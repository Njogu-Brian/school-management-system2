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
                <p class="text-muted mb-0">Manage trips and assign vehicles to routes.</p>
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
                <h5 class="mb-0">All Trips</h5>
                <span class="input-chip">{{ $trips->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Trip Name</th>
                                <th>Type</th>
                                <th>Route</th>
                                <th>Vehicle</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trips as $trip)
                                <tr>
                                    <td class="fw-semibold">{{ $trip->name }}</td>
                                    <td><span class="pill-badge">{{ $trip->type }}</span></td>
                                    <td>{{ $trip->route->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->vehicle->vehicle_number ?? 'N/A' }}</td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('transport.trips.edit', $trip->id) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transport.trips.destroy', $trip->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this trip?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No trips found.</td>
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

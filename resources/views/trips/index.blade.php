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
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $group)
                                @php
                                    $morning = $group['morning'];
                                    $evening = $group['evening'];
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
                                        @if($morning)
                                            <div class="fw-semibold">{{ $morning->name }}</div>
                                            <a href="{{ route('transport.trips.assign', $morning) }}" class="btn btn-sm btn-settings-primary mt-1">
                                                <i class="bi bi-people"></i> Assign
                                            </a>
                                            <a href="{{ route('transport.trips.edit', $morning) }}" class="btn btn-sm btn-ghost-strong mt-1">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">No morning trip</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($evening)
                                            <div class="fw-semibold">{{ $evening->name }}</div>
                                            <a href="{{ route('transport.trips.assign', $evening) }}" class="btn btn-sm btn-settings-primary mt-1">
                                                <i class="bi bi-people"></i> Assign
                                            </a>
                                            <a href="{{ route('transport.trips.edit', $evening) }}" class="btn btn-sm btn-ghost-strong mt-1">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">No evening trip</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @foreach ($group['other'] as $extra)
                                            <div class="mb-2 text-start">
                                                <small class="text-muted d-block">{{ $extra->name }} ({{ $extra->type ?: $extra->direction ?: 'other' }})</small>
                                                <a href="{{ route('transport.trips.assign', $extra) }}" class="btn btn-sm btn-ghost-strong">Assign</a>
                                                <a href="{{ route('transport.trips.edit', $extra) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a>
                                            </div>
                                        @endforeach
                                        @if($morning)
                                            <form action="{{ route('transport.trips.destroy', $morning) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete morning trip? Students on it will be unassigned from that leg.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete morning">M <i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                        @if($evening)
                                            <form action="{{ route('transport.trips.destroy', $evening) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete evening trip? Students on it will be unassigned from that leg.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete evening">E <i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
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

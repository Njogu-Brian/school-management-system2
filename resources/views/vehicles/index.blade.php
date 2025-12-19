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
                <h1 class="mb-1">Vehicles</h1>
                <p class="text-muted mb-0">Manage fleet, drivers, and documents.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('transport.vehicles.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Add Vehicle
                </a>
            </div>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>There were some problems:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Fleet</h5>
                <span class="input-chip">{{ $vehicles->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Assigned Students</th>
                                <th>Docs</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicles as $vehicle)
                                <tr>
                                    <td class="fw-semibold">{{ $vehicle->vehicle_number }}</td>
                                    <td>{{ $vehicle->driver_name ?? '—' }}</td>
                                    <td>
                                        @forelse($vehicle->trips as $trip)
                                            <div class="text-muted small">
                                                {{ $trip->student->name ?? $trip->student->full_name ?? 'Student' }}
                                                <span class="input-chip ms-1">{{ $trip->student->class ?? $trip->student->classrooms->name ?? '' }}</span>
                                            </div>
                                        @empty
                                            <span class="text-muted">None</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if($vehicle->insurance_document)
                                            <a href="{{ asset('storage/'.$vehicle->insurance_document) }}" target="_blank" class="btn btn-sm btn-ghost-strong">Insurance</a>
                                        @endif
                                        @if($vehicle->logbook_document)
                                            <a href="{{ asset('storage/'.$vehicle->logbook_document) }}" target="_blank" class="btn btn-sm btn-ghost-strong">Logbook</a>
                                        @endif
                                        @if(!$vehicle->insurance_document && !$vehicle->logbook_document)
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('transport.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transport.vehicles.destroy', $vehicle) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this vehicle?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No vehicles found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assign Driver</h5>
                <span class="text-muted small">Quickly link drivers to vehicles</span>
            </div>
            <div class="card-body">
                <form action="{{ route('transport.assign.driver') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="vehicle_id" class="form-label fw-semibold">Select Vehicle</label>
                        <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">
                                    {{ $vehicle->vehicle_number }}
                                    {{ $vehicle->driver_name ? ' - Current Driver: '.$vehicle->driver_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="driver_name" class="form-label fw-semibold">Select Driver</label>
                        <select name="driver_name" id="driver_name" class="form-select" required>
                            <option value="">-- Select Driver --</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->name }}">{{ $driver->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-settings-primary">Assign Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

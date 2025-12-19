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
                <h1 class="mb-1">Create Trip</h1>
                <p class="text-muted mb-0">Configure trip details, route, and vehicle.</p>
            </div>
            <a href="{{ route('transport.trips.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.trips.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">Trip Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="type" class="form-label fw-semibold">Trip Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="Morning">Morning</option>
                            <option value="Evening">Evening</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="route_id" class="form-label fw-semibold">Select Route</label>
                        <select name="route_id" id="route_id" class="form-select" required>
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_id" class="form-label fw-semibold">Select Vehicle</label>
                        <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('transport.trips.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Create Trip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

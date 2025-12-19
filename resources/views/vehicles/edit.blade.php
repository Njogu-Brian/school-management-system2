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
                <h1 class="mb-1">Edit Vehicle</h1>
                <p class="text-muted mb-0">Update fleet details and documents.</p>
            </div>
            <a href="{{ route('transport.vehicles.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-6">
                        <label for="vehicle_number" class="form-label fw-semibold">Vehicle Number</label>
                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control"
                               value="{{ old('vehicle_number', $vehicle->vehicle_number) }}" required>
                        @error('vehicle_number') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="capacity" class="form-label fw-semibold">Capacity</label>
                        <input type="number" name="capacity" id="capacity" class="form-control" value="{{ old('capacity', $vehicle->capacity) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="make" class="form-label fw-semibold">Make</label>
                        <input type="text" name="make" id="make" class="form-control" value="{{ old('make', $vehicle->make) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="model" class="form-label fw-semibold">Model</label>
                        <input type="text" name="model" id="model" class="form-control" value="{{ old('model', $vehicle->model) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label fw-semibold">Vehicle Type</label>
                        <input type="text" name="type" id="type" class="form-control" value="{{ old('type', $vehicle->type) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="chassis_number" class="form-label fw-semibold">Chassis Number</label>
                        <input type="text" name="chassis_number" id="chassis_number" class="form-control" value="{{ old('chassis_number', $vehicle->chassis_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="insurance_document" class="form-label fw-semibold">Insurance Document</label>
                        <input type="file" name="insurance_document" id="insurance_document" class="form-control">
                        @if($vehicle->insurance_document)
                            <small>Current: <a href="{{ asset('storage/'.$vehicle->insurance_document) }}" target="_blank">View</a></small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label for="logbook_document" class="form-label fw-semibold">Logbook Document</label>
                        <input type="file" name="logbook_document" id="logbook_document" class="form-control">
                        @if($vehicle->logbook_document)
                            <small>Current: <a href="{{ asset('storage/'.$vehicle->logbook_document) }}" target="_blank">View</a></small>
                        @endif
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('transport.vehicles.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Update Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

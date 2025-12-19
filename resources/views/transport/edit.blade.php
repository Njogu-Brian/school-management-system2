@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Transport / Vehicles</div>
                <h1>Edit Vehicle</h1>
                <p>Update vehicle details.</p>
            </div>
            <a href="{{ route('vehicles.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('vehicles.update', $vehicle->id) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" class="form-control" value="{{ $vehicle->vehicle_number }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Make</label>
                        <input type="text" name="make" class="form-control" value="{{ $vehicle->make }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" value="{{ $vehicle->model }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" class="form-control" value="{{ $vehicle->type }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="{{ $vehicle->capacity }}">
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


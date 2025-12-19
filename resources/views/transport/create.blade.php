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
                <h1>Add Vehicle</h1>
                <p>Create a new transport vehicle record.</p>
            </div>
            <a href="{{ route('vehicles.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('vehicles.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Make</label>
                        <input type="text" name="make" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control">
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


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
                <p class="text-muted mb-0">Configure trip details and vehicle.</p>
            </div>
            <a href="{{ route('transport.trips.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.trips.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">Trip Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="type" class="form-label fw-semibold">Trip Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">Select Type</option>
                            <option value="Morning" {{ old('type') == 'Morning' ? 'selected' : '' }}>Morning</option>
                            <option value="Evening" {{ old('type') == 'Evening' ? 'selected' : '' }}>Evening</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_id" class="form-label fw-semibold">Select Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                            <option value="">Select Vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_number }} - {{ $vehicle->driver_name ?? 'No Driver' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="driver_id" class="form-label fw-semibold">Driver</label>
                        <select name="driver_id" id="driver_id" class="form-select">
                            <option value="">Select Driver (Optional)</option>
                            @foreach (\App\Models\Staff::whereHas('user.roles', function($q) { $q->where('name', 'Driver'); })->with('user')->get() as $staff)
                                <option value="{{ $staff->id }}" {{ old('driver_id') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->user->name ?? $staff->first_name . ' ' . $staff->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="direction" class="form-label fw-semibold">Direction</label>
                        <select name="direction" id="direction" class="form-select">
                            <option value="">All Directions</option>
                            <option value="pickup" {{ old('direction') == 'pickup' ? 'selected' : '' }}>Pickup</option>
                            <option value="dropoff" {{ old('direction') == 'dropoff' ? 'selected' : '' }}>Drop-off</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="day_of_week" class="form-label fw-semibold">Days of Week</label>
                        <select name="day_of_week[]" id="day_of_week" class="form-select" multiple size="7">
                            <option value="1" {{ in_array('1', old('day_of_week', [])) ? 'selected' : '' }}>Monday</option>
                            <option value="2" {{ in_array('2', old('day_of_week', [])) ? 'selected' : '' }}>Tuesday</option>
                            <option value="3" {{ in_array('3', old('day_of_week', [])) ? 'selected' : '' }}>Wednesday</option>
                            <option value="4" {{ in_array('4', old('day_of_week', [])) ? 'selected' : '' }}>Thursday</option>
                            <option value="5" {{ in_array('5', old('day_of_week', [])) ? 'selected' : '' }}>Friday</option>
                            <option value="6" {{ in_array('6', old('day_of_week', [])) ? 'selected' : '' }}>Saturday</option>
                            <option value="7" {{ in_array('7', old('day_of_week', [])) ? 'selected' : '' }}>Sunday</option>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple days. Leave empty for trips that run all days.</small>
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

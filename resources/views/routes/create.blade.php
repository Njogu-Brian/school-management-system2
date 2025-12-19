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
                <h1 class="mb-1">Add Route</h1>
                <p class="text-muted mb-0">Create a new route and assign vehicles.</p>
            </div>
            <a href="{{ route('transport.routes.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.routes.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">Route Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="area" class="form-label fw-semibold">Area</label>
                        <input type="text" name="area" id="area" class="form-control" value="{{ old('area') }}">
                    </div>
                    <div class="col-12">
                        <label for="vehicle_ids" class="form-label fw-semibold">Assign Vehicles</label>
                        <select name="vehicle_ids[]" id="vehicle_ids" class="form-select" multiple>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}"
                                    @if(isset($route) && $route->vehicles->contains($vehicle->id)) selected @endif>
                                    {{ $vehicle->vehicle_number }} (Driver: {{ $vehicle->driver_name ?? 'Unassigned' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to multi-select.</small>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('transport.routes.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button class="btn btn-settings-primary">Save Route</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

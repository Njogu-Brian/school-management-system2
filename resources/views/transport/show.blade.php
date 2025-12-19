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
                <h1>Vehicle Details</h1>
            </div>
            <a href="{{ route('vehicles.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-md-3">Vehicle Number</dt><dd class="col-md-9">{{ $vehicle->vehicle_number }}</dd>
                    <dt class="col-md-3">Make</dt><dd class="col-md-9">{{ $vehicle->make }}</dd>
                    <dt class="col-md-3">Model</dt><dd class="col-md-9">{{ $vehicle->model }}</dd>
                    <dt class="col-md-3">Type</dt><dd class="col-md-9">{{ $vehicle->type }}</dd>
                    <dt class="col-md-3">Capacity</dt><dd class="col-md-9">{{ $vehicle->capacity }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection


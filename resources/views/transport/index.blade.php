@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Transport</div>
                <h1>Transport Management</h1>
                <p>Manage vehicles and routes.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('vehicles.create') }}" class="btn btn-settings-primary"><i class="bi bi-truck"></i> Add Vehicle</a>
                <a href="{{ route('routes.create') }}" class="btn btn-ghost-strong"><i class="bi bi-signpost-split"></i> Add Route</a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Vehicles</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Vehicle listing not yet implemented.</p>
            </div>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Routes</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Route listing not yet implemented.</p>
            </div>
        </div>
    </div>
</div>
@endsection


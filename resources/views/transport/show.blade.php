@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Transport Details</h1>
    <p><strong>Vehicle Number:</strong> {{ $vehicle->vehicle_number }}</p>
    <p><strong>Make:</strong> {{ $vehicle->make }}</p>
    <p><strong>Model:</strong> {{ $vehicle->model }}</p>
    <p><strong>Type:</strong> {{ $vehicle->type }}</p>
    <p><strong>Capacity:</strong> {{ $vehicle->capacity }}</p>

    <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Back to Transport List</a>
</div>
@endsection

@extends('layouts.app')

@section('content')
<h1>Edit Vehicle</h1>

<form action="{{ route('vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="mb-3">
        <label for="vehicle_number">Vehicle Number</label>
        <input type="text" name="vehicle_number" class="form-control" value="{{ $vehicle->vehicle_number }}" required>
    </div>

    <div class="mb-3">
        <label for="make">Make</label>
        <input type="text" name="make" class="form-control" value="{{ $vehicle->make }}">
    </div>

    <div class="mb-3">
        <label for="model">Model</label>
        <input type="text" name="model" class="form-control" value="{{ $vehicle->model }}">
    </div>

    <div class="mb-3">
        <label for="type">Vehicle Type</label>
        <input type="text" name="type" class="form-control" value="{{ $vehicle->type }}">
    </div>

    <div class="mb-3">
        <label for="capacity">Capacity</label>
        <input type="number" name="capacity" class="form-control" value="{{ $vehicle->capacity }}">
    </div>

    <div class="mb-3">
        <label for="chassis_number">Chassis Number</label>
        <input type="text" name="chassis_number" class="form-control" value="{{ $vehicle->chassis_number }}">
    </div>

    <div class="mb-3">
        <label for="insurance_document">Insurance Document</label>
        <input type="file" name="insurance_document" class="form-control">
        @if($vehicle->insurance_document)
            <small>Current File: <a href="{{ asset('storage/' . $vehicle->insurance_document) }}" target="_blank">View</a></small>
        @endif
    </div>

    <div class="mb-3">
        <label for="logbook_document">Logbook Document</label>
        <input type="file" name="logbook_document" class="form-control">
        @if($vehicle->logbook_document)
            <small>Current File: <a href="{{ asset('storage/' . $vehicle->logbook_document) }}" target="_blank">View</a></small>
        @endif
    </div>

    <button type="submit" class="btn btn-primary">Update Vehicle</button>
</form>
@endsection

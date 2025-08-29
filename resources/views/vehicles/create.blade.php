@extends('layouts.app')

@section('content')
<h1>Add New Vehicle</h1>

<form action="{{ route('transport.vehicles.store') }}" method="POST" enctype="multipart/form-data" class="card p-3">
    @csrf

    <div class="mb-3">
        <label for="vehicle_number" class="form-label">Vehicle Number</label>
        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" value="{{ old('vehicle_number') }}" required>
        @error('vehicle_number') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="make" class="form-label">Make</label>
        <input type="text" name="make" id="make" class="form-control" value="{{ old('make') }}">
    </div>

    <div class="mb-3">
        <label for="model" class="form-label">Model</label>
        <input type="text" name="model" id="model" class="form-control" value="{{ old('model') }}">
    </div>

    <div class="mb-3">
        <label for="type" class="form-label">Vehicle Type</label>
        <input type="text" name="type" id="type" class="form-control" value="{{ old('type') }}">
    </div>

    <div class="mb-3">
        <label for="capacity" class="form-label">Capacity</label>
        <input type="number" name="capacity" id="capacity" class="form-control" value="{{ old('capacity') }}">
    </div>

    <div class="mb-3">
        <label for="chassis_number" class="form-label">Chassis Number</label>
        <input type="text" name="chassis_number" id="chassis_number" class="form-control" value="{{ old('chassis_number') }}">
    </div>

    <div class="mb-3">
        <label for="insurance_document" class="form-label">Insurance Document</label>
        <input type="file" name="insurance_document" id="insurance_document" class="form-control">
    </div>

    <div class="mb-3">
        <label for="logbook_document" class="form-label">Logbook Document</label>
        <input type="file" name="logbook_document" id="logbook_document" class="form-control">
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">Add Vehicle</button>
        <a href="{{ route('transport.vehicles.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>
@endsection

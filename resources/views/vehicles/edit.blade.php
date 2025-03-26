@extends('layouts.app')

@section('content')
<h1>Edit Vehicle</h1>

<form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
    @csrf @method('PUT')
    <div class="mb-3">
        <label for="vehicle_number">Vehicle Number</label>
        <input type="text" name="vehicle_number" class="form-control" value="{{ $vehicle->vehicle_number }}" required>
    </div>
    <div class="mb-3">
        <label for="driver_name">Driver Name</label>
        <input type="text" name="driver_name" class="form-control" value="{{ $vehicle->driver_name }}" required>
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Transport Record</h1>
    <form action="{{ route('vehicles.update', $vehicle->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="vehicle_number">Vehicle Number:</label>
        <input type="text" name="vehicle_number" class="form-control" value="{{ $vehicle->vehicle_number }}" required>

        <label for="make">Make:</label>
        <input type="text" name="make" class="form-control" value="{{ $vehicle->make }}">

        <label for="model">Model:</label>
        <input type="text" name="model" class="form-control" value="{{ $vehicle->model }}">

        <label for="type">Type:</label>
        <input type="text" name="type" class="form-control" value="{{ $vehicle->type }}">

        <label for="capacity">Capacity:</label>
        <input type="number" name="capacity" class="form-control" value="{{ $vehicle->capacity }}">

        <button type="submit" class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection

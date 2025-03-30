@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Transport Record</h1>
    <form action="{{ route('vehicles.store') }}" method="POST">
        @csrf
        <label for="vehicle_number">Vehicle Number:</label>
        <input type="text" name="vehicle_number" class="form-control" required>

        <label for="make">Make:</label>
        <input type="text" name="make" class="form-control">

        <label for="model">Model:</label>
        <input type="text" name="model" class="form-control">

        <label for="type">Type:</label>
        <input type="text" name="type" class="form-control">

        <label for="capacity">Capacity:</label>
        <input type="number" name="capacity" class="form-control">

        <button type="submit" class="btn btn-primary mt-3">Save</button>
    </form>
</div>
@endsection

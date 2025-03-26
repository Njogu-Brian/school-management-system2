@extends('layouts.app')

@section('content')
<h1>Add Vehicle</h1>

<form action="{{ route('vehicles.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="vehicle_number">Vehicle Number</label>
        <input type="text" name="vehicle_number" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="driver_name">Driver Name</label>
        <input type="text" name="driver_name" class="form-control" required>
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection

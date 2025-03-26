@extends('layouts.app')

@section('content')
<h1>Add New Vehicle</h1>

<form action="{{ route('vehicles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
        <label for="vehicle_number">Vehicle Number</label>
        <input type="text" name="vehicle_number" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="make">Make</label>
        <input type="text" name="make" class="form-control">
    </div>

    <div class="mb-3">
        <label for="model">Model</label>
        <input type="text" name="model" class="form-control">
    </div>

    <div class="mb-3">
        <label for="type">Vehicle Type</label>
        <input type="text" name="type" class="form-control">
    </div>

    <div class="mb-3">
        <label for="capacity">Capacity</label>
        <input type="number" name="capacity" class="form-control">
    </div>

    <div class="mb-3">
        <label for="chassis_number">Chassis Number</label>
        <input type="text" name="chassis_number" class="form-control">
    </div>

    <div class="mb-3">
        <label for="insurance_document">Insurance Document</label>
        <input type="file" name="insurance_document" class="form-control">
    </div>

    <div class="mb-3">
        <label for="logbook_document">Logbook Document</label>
        <input type="file" name="logbook_document" class="form-control">
    </div>

    <button type="submit" class="btn btn-success">Add Vehicle</button>
</form>
@endsection

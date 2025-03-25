@extends('layouts.app')

@section('content')
<h1>Transport Management</h1>

<!-- Assign Students to Routes -->
<h3>Assign Students to Routes</h3>
<form action="{{ route('transport.assign.student') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="student_id">Select Student</label>
        <select name="student_id" id="student_id" class="form-control" required>
            @foreach ($students as $student)
                <option value="{{ $student->id }}">{{ $student->name }} (Class: {{ $student->class }})</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="transport_id">Select Route</label>
        <select name="transport_id" id="transport_id" class="form-control" required>
            @foreach ($transports as $transport)
                <option value="{{ $transport->id }}">Vehicle: {{ $transport->vehicle_number }} (Driver: {{ $transport->driver_name }})</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="drop_off_point">Drop-Off Point</label>
        <input type="text" name="drop_off_point" id="drop_off_point" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Assign Student</button>
</form>

<hr>

<!-- Assign Drivers to Vehicles -->
<h3>Assign Drivers to Vehicles</h3>
<form action="{{ route('transport.assign.driver') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="driver_name">Driver Name</label>
        <input type="text" name="driver_name" id="driver_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="vehicle_number">Vehicle Number</label>
        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Assign Driver</button>
</form>
@endsection
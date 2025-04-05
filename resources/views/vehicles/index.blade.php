@extends('layouts.app')

@section('content')
<h1>Vehicles</h1>

<a href="{{ route('vehicles.create') }}" class="btn btn-success mb-3">Add New Vehicle</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Vehicle Number</th>
            <th>Driver Name</th>
            <th>Assigned Students</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->vehicle_number }}</td>
                <td>{{ $vehicle->driver_name }}</td>
                <td>
                    @foreach($vehicle->trips as $trip)
                        <div>{{ $trip->student->name }} (Class: {{ $trip->student->class }})</div>
                    @endforeach
                </td>
                <td>
                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this vehicle?')">Delete</button>
                    </form>
                </td>
                <td>
                    @if($vehicle->insurance_document)
                        <a href="{{ asset('storage/' . $vehicle->insurance_document) }}" target="_blank">Insurance Document</a><br>
                    @endif
                    @if($vehicle->logbook_document)
                        <a href="{{ asset('storage/' . $vehicle->logbook_document) }}" target="_blank">Logbook Document</a>
                    @endif
                </td>
          </tr>
        @endforeach
    </tbody>
</table>

<hr>
<h3>Assign Driver to Existing Vehicle</h3>
<form action="{{ route('transport.assign.driver') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="vehicle_id">Select Vehicle</label>
        <select name="vehicle_id" class="form-control" required>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}">
                    {{ $vehicle->vehicle_number }} {{ $vehicle->driver_name ? "- Current Driver: $vehicle->driver_name" : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="driver_name">Select Driver</label>
        <select name="driver_name" class="form-control" required>
            <option value="">-- Select Driver --</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->name }}">{{ $driver->name }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Assign Driver</button>
</form>
@endsection

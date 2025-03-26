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
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->vehicle_number }}</td>
                <td>{{ $vehicle->driver_name }}</td>
                <td>
                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this vehicle?')">Delete</button>
                    </form>
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
        <label for="driver_name">Driver Name</label>
        <input type="text" name="driver_name" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Assign Driver</button>
</form>

@endsection

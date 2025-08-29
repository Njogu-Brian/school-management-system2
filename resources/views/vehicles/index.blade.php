@extends('layouts.app')

@section('content')
<h1>Vehicles</h1>

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('transport.vehicles.create') }}" class="btn btn-success">Add New Vehicle</a>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>There were some problems:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Vehicle Number</th>
            <th>Driver Name</th>
            <th>Assigned Students</th>
            <th>Docs</th>
            <th style="width:180px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->vehicle_number }}</td>
                <td>{{ $vehicle->driver_name ?? '—' }}</td>
                <td>
                    @forelse($vehicle->trips as $trip)
                        <div>{{ $trip->student->name }} (Class: {{ $trip->student->class }})</div>
                    @empty
                        <span class="text-muted">None</span>
                    @endforelse
                </td>
                <td>
                    @if($vehicle->insurance_document)
                        <a href="{{ asset('storage/'.$vehicle->insurance_document) }}" target="_blank">Insurance</a><br>
                    @endif
                    @if($vehicle->logbook_document)
                        <a href="{{ asset('storage/'.$vehicle->logbook_document) }}" target="_blank">Logbook</a>
                    @endif
                    @if(!$vehicle->insurance_document && !$vehicle->logbook_document)
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('transport.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('transport.vehicles.destroy', $vehicle) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this vehicle?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No vehicles found</td></tr>
        @endforelse
    </tbody>
</table>

<hr>
<h3>Assign Driver to Existing Vehicle</h3>
<form action="{{ route('transport.assign.driver') }}" method="POST" class="card p-3">
    @csrf
    <div class="mb-3">
        <label for="vehicle_id" class="form-label">Select Vehicle</label>
        <select name="vehicle_id" id="vehicle_id" class="form-select" required>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}">
                    {{ $vehicle->vehicle_number }}
                    {{ $vehicle->driver_name ? ' - Current Driver: '.$vehicle->driver_name : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="driver_name" class="form-label">Select Driver</label>
        <select name="driver_name" id="driver_name" class="form-select" required>
            <option value="">-- Select Driver --</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->name }}">{{ $driver->name }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Assign Driver</button>
</form>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Trips Management</h1>
    <a href="{{ route('transport.trips.create') }}" class="btn btn-success mb-3">Create New Trip</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Trip Name</th>
                <th>Type</th>
                <th>Route</th>
                <th>Vehicle</th>
                <th style="width:180px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trips as $trip)
                <tr>
                    <td>{{ $trip->name }}</td>
                    <td>{{ $trip->type }}</td>
                    <td>{{ $trip->route->name ?? 'N/A' }}</td>
                    <td>{{ $trip->vehicle->vehicle_number ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('transport.trips.edit', $trip->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('transport.trips.destroy', $trip->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this trip?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No trips found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

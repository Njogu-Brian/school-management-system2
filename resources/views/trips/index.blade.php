@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Trips Management</h1>
    <a href="{{ route('trips.create') }}" class="btn btn-success mb-3">Create New Trip</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Trip Name</th>
                <th>Route</th>
                <th>Vehicle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trips as $trip)
                <tr>
                    <td>{{ $trip->trip_name }}</td>
                    <td>{{ $trip->route->name ?? 'N/A' }}</td>
                    <td>{{ $trip->vehicle->vehicle_number ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('trips.edit', $trip->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('trips.destroy', $trip->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No trips found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

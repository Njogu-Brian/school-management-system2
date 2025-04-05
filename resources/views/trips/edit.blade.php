@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Trip</h1>

    <form action="{{ route('trips.update', $trip->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Trip Name -->
        <div class="mb-3">
            <label for="name">Trip Name</label>
            <input type="text" name="name" class="form-control" value="{{ $trip->name }}" required>
        </div>

        <!-- Trip Type -->
        <div class="mb-3">
            <label for="type">Trip Type</label>
            <select name="type" class="form-control" required>
                <option value="Morning" {{ $trip->type == 'Morning' ? 'selected' : '' }}>Morning</option>
                <option value="Evening" {{ $trip->type == 'Evening' ? 'selected' : '' }}>Evening</option>
            </select>
        </div>

        <!-- Route Selection -->
        <div class="mb-3">
            <label for="route_id">Select Route</label>
            <select name="route_id" class="form-control" required>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}" {{ $trip->route_id == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Vehicle Selection -->
        <div class="mb-3">
            <label for="vehicle_id">Select Vehicle</label>
            <select name="vehicle_id" class="form-control" required>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" {{ $trip->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                        {{ $vehicle->vehicle_number }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Trip</button>
    </form>
</div>
@endsection

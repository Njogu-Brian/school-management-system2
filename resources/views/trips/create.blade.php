@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create New Trip</h1>

    <form action="{{ route('trips.store') }}" method="POST">
        @csrf

        <!-- Trip Name -->
        <div class="mb-3">
            <label for="name">Trip Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <!-- Trip Type -->
        <div class="mb-3">
            <label for="type">Trip Type</label>
            <select name="type" class="form-control" required>
                <option value="Morning">Morning</option>
                <option value="Evening">Evening</option>
            </select>
        </div>

        <!-- Route Selection -->
        <div class="mb-3">
            <label for="route_id">Select Route</label>
            <select name="route_id" class="form-control" required>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Vehicle Selection -->
        <div class="mb-3">
            <label for="vehicle_id">Select Vehicle</label>
            <select name="vehicle_id" class="form-control" required>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Create Trip</button>
    </form>
</div>
@endsection

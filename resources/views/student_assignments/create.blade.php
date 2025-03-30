@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Assign Student to Route</h1>

    <form action="{{ route('student_assignments.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="student_id">Select Student</label>
            <select name="student_id" class="form-control">
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->getFullNameAttribute() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="route_id">Select Route</label>
            <select name="route_id" class="form-control">
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="trip_id">Select Trip</label>
            <select name="trip_id" class="form-control">
                @foreach ($trips as $trip)
                    <option value="{{ $trip->id }}">{{ $trip->trip_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="vehicle_id">Select Vehicle (Optional)</label>
            <select name="vehicle_id" class="form-control">
                <option value="">-- None --</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="drop_off_point_id">Select Drop-Off Point</label>
            <select name="drop_off_point_id" class="form-control">
                @foreach ($dropOffPoints as $point)
                    <option value="{{ $point->id }}">{{ $point->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Assign Student</button>
    </form>
</div>
@endsection

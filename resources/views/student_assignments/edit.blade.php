@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Student Assignment</h1>

    <form action="{{ route('student_assignments.update', $assignment->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="student_id">Select Student</label>
            <select name="student_id" class="form-control">
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" {{ $assignment->student_id == $student->id ? 'selected' : '' }}>
                        {{ $student->getFullNameAttribute() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="route_id">Select Route</label>
            <select name="route_id" class="form-control">
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}" {{ $assignment->route_id == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="trip_id">Select Trip</label>
            <select name="trip_id" class="form-control">
                @foreach ($trips as $trip)
                    <option value="{{ $trip->id }}" {{ $assignment->trip_id == $trip->id ? 'selected' : '' }}>
                        {{ $trip->trip_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="vehicle_id">Select Vehicle (Optional)</label>
            <select name="vehicle_id" class="form-control">
                <option value="">-- None --</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" {{ $assignment->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                        {{ $vehicle->vehicle_number }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="drop_off_point_id">Select Drop-Off Point</label>
            <select name="drop_off_point_id" class="form-control">
                @foreach ($dropOffPoints as $point)
                    <option value="{{ $point->id }}" {{ $assignment->drop_off_point_id == $point->id ? 'selected' : '' }}>
                        {{ $point->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Assignment</button>
    </form>
</div>
@endsection

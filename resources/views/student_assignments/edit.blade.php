@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Student Assignment</h1>

    <form action="{{ route('student_assignments.update', $assignment->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Student Selection -->
        <div class="mb-3">
            <label for="student_id">Select Student</label>
            <select name="student_id" class="form-control">
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" {{ $assignment->student_id == $student->id ? 'selected' : '' }}>
                        {{ $student->first_name }} {{ $student->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Route Selection -->
        <div class="mb-3">
            <label for="route_id">Select Route</label>
            <select name="route_id" class="form-control" id="route-select">
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}" {{ $assignment->route_id == $route->id ? 'selected' : '' }}>
                        {{ $route->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Trip Selection -->
        <div class="mb-3">
            <label for="trip_id">Select Trip</label>
            <select name="trip_id" class="form-control" id="trip-select">
                @foreach ($trips as $trip)
                    <option value="{{ $trip->id }}" data-route="{{ $trip->route_id }}" {{ $assignment->trip_id == $trip->id ? 'selected' : '' }}>
                        {{ $trip->trip_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Drop-Off Point Selection -->
        <div class="mb-3">
            <label for="drop_off_point_id">Select Drop-Off Point</label>
            <select name="drop_off_point_id" class="form-control" id="drop-off-point-select">
                @foreach ($dropOffPoints as $point)
                    <option value="{{ $point->id }}" data-route="{{ $point->route_id }}" {{ $assignment->drop_off_point_id == $point->id ? 'selected' : '' }}>
                        {{ $point->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Assignment</button>
    </form>
</div>
@endsection

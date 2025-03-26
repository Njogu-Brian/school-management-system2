@extends('layouts.app')

@section('content')
<h1>Routes</h1>

<a href="{{ route('routes.create') }}" class="btn btn-success mb-3">Add New Route</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Route Name</th>
            <th>Area</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($routes as $route)
            <tr>
                <td>{{ $route->name }}</td>
                <td>{{ $route->area }}</td>
                <td>
                    <a href="{{ route('routes.edit', $route) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('routes.destroy', $route) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this route?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<hr>
<h3>Assign Student to Route</h3>
<form action="{{ route('transport.assign.student') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="student_id">Select Student</label>
        <select name="student_id" class="form-control" required>
            @foreach ($students as $student)
                <option value="{{ $student->id }}">{{ $student->name }} (Class: {{ $student->class }})</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="route_id">Select Route</label>
        <select name="route_id" class="form-control" required>
            @foreach ($routes as $route)
                <option value="{{ $route->id }}">{{ $route->name }} - {{ $route->area }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="vehicle_id">Select Vehicle (optional)</label>
        <select name="vehicle_id" class="form-control">
            <option value="">-- None --</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }} - Driver: {{ $vehicle->driver_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="drop_off_point">Drop-Off Point</label>
        <input type="text" name="drop_off_point" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Assign Student</button>
</form>

@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Assignments</h1>
    <a href="{{ route('student_assignments.create') }}" class="btn btn-success mb-3">Assign Student</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Student</th>
                <th>Route</th>
                <th>Trip</th>
                <th>Vehicle</th>
                <th>Drop-Off Point</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->student->getFullNameAttribute() }}</td>
                    <td>{{ $assignment->route->name }}</td>
                    <td>{{ $assignment->trip->trip_name }}</td>
                    <td>{{ $assignment->vehicle->vehicle_number ?? 'N/A' }}</td>
                    <td>{{ $assignment->dropOffPoint->name }}</td>
                    <td>
                        <a href="{{ route('student_assignments.edit', $assignment->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('student_assignments.destroy', $assignment->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No assignments found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

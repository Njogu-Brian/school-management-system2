@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Assignments</h1>
    <a href="{{ route('transport.student-assignments.create') }}" class="btn btn-success mb-3">Assign Student</a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Student</th>
                <th>Route</th>
                <th>Trip</th>
                <th>Vehicle</th>
                <th>Drop-Off Point</th>
                <th style="width:180px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->student->full_name ?? $assignment->student->first_name.' '.$assignment->student->last_name }}</td>
                    <td>{{ $assignment->route->name ?? 'N/A' }}</td>
                    <td>{{ $assignment->trip->name ?? 'N/A' }}</td>
                    <td>{{ $assignment->vehicle->vehicle_number ?? 'N/A' }}</td>
                    <td>{{ $assignment->dropOffPoint->name ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('transport.student-assignments.edit', $assignment->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('transport.student-assignments.destroy', $assignment->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this assignment?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No assignments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Assignment Details</h1>

    <p><strong>Student:</strong> {{ $assignment->student->full_name ?? 'N/A' }}</p>
    <p><strong>Route:</strong> {{ $assignment->route->name ?? 'N/A' }}</p>
    <p><strong>Trip:</strong> {{ $assignment->trip->name ?? 'N/A' }}</p>
    <p><strong>Vehicle:</strong> {{ $assignment->vehicle->vehicle_number ?? 'N/A' }}</p>
    <p><strong>Drop-Off Point:</strong> {{ $assignment->dropOffPoint->name ?? 'N/A' }}</p>

    <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-light">Back</a>
</div>
@endsection

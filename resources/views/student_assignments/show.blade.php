@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Assignment Details</h1>

    <p><strong>Student:</strong> {{ $assignment->student->full_name ?? 'N/A' }}</p>
    <p><strong>Morning Trip:</strong> {{ $assignment->morningTrip->name ?? 'N/A' }}</p>
    <p><strong>Morning Drop-Off Point:</strong> {{ $assignment->morningDropOffPoint->name ?? 'N/A' }}</p>
    <p><strong>Evening Trip:</strong> {{ $assignment->eveningTrip->name ?? 'N/A' }}</p>
    <p><strong>Evening Drop-Off Point:</strong> {{ $assignment->eveningDropOffPoint->name ?? 'N/A' }}</p>

    <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-light">Back</a>
</div>
@endsection

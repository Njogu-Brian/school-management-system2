@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Details</h1>
    
    <p><strong>Admission Number:</strong> {{ $student->admission_number }}</p>
    <p><strong>Name:</strong> {{ $student->name }}</p>
    <p><strong>Class:</strong> {{ $student->classroom->name ?? 'N/A' }}</p>
    <p><strong>Stream:</strong> {{ $student->stream->name ?? 'N/A' }}</p>
    <p><strong>Category:</strong> {{ $student->studentCategory->name ?? 'N/A' }}</p>

    <h4>Parent Details</h4>
    <p><strong>Father's Name:</strong> {{ $student->parent->father_name }}</p>
    <p><strong>Mother's Name:</strong> {{ $student->parent->mother_name }}</p>
    <p><strong>Guardian's Name:</strong> {{ $student->parent->guardian_name }}</p>

    <a href="{{ route('students.index') }}" class="btn btn-primary">Back to Students</a>
</div>
@endsection

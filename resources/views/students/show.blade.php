@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Details</h1>
    
    <h4>Student Information</h4>
    <p><strong>Admission Number:</strong> {{ $student->admission_number }}</p>
    <p><strong>Name:</strong> {{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</p>
    <p><strong>Gender:</strong> {{ $student->gender }}</p>
    <p><strong>Date of Birth:</strong> {{ $student->dob }}</p>
    <p><strong>Class:</strong> {{ $student->classroom->name ?? 'N/A' }}</p>
    <p><strong>Stream:</strong> {{ $student->stream->name ?? 'N/A' }}</p>
    <p><strong>Category:</strong> {{ $student->category->name ?? 'N/A' }}</p>
    <p><strong>NEMIS Number:</strong> {{ $student->nemis_number }}</p>
    <p><strong>KNEC Assessment Number:</strong> {{ $student->knec_assessment_number }}</p>

    {{-- Parent Details --}}
    <h4>Parent/Guardian Details</h4>
    @if ($student->parent)
        <p><strong>Father's Name:</strong> {{ $student->parent->father_name ?? 'N/A' }}</p>
        <p><strong>Mother's Name:</strong> {{ $student->parent->mother_name ?? 'N/A' }}</p>
        <p><strong>Guardian's Name:</strong> {{ $student->parent->guardian_name ?? 'N/A' }}</p>
    @else
        <p>No parent/guardian information available.</p>
    @endif

    {{-- Documents --}}
    <h4>Uploaded Documents</h4>
    @if ($student->passport_photo)
        <p><strong>Passport Photo:</strong> <a href="{{ asset('storage/' . $student->passport_photo) }}" target="_blank">View Photo</a></p>
    @endif
    @if ($student->birth_certificate)
        <p><strong>Birth Certificate:</strong> <a href="{{ asset('storage/' . $student->birth_certificate) }}" target="_blank">View Certificate</a></p>
    @endif
    @if ($student->parent_id_card)
        <p><strong>Parent's ID:</strong> <a href="{{ asset('storage/' . $student->parent_id_card) }}" target="_blank">View Parent's ID</a></p>
    @endif

    {{-- Back Button --}}
    <a href="{{ route('students.index') }}" class="btn btn-primary">Back to Students</a>
</div>
@endsection

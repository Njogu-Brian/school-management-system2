@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Student Details</h1>
    
    {{-- Student Information --}}
    <div class="card mb-4">
        <div class="card-header">Student Information</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><strong>Admission Number:</strong> {{ $student->admission_number }}</div>
                <div class="col-md-6"><strong>Name:</strong> {{ $student->first_name }} {{ $student->middle_name ?? '' }} {{ $student->last_name }}</div>
                <div class="col-md-6"><strong>Gender:</strong> {{ $student->gender }}</div>
                <div class="col-md-6"><strong>Date of Birth:</strong> {{ $student->dob }}</div>
                <div class="col-md-6"><strong>Class:</strong> {{ $student->classrooms->name ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Stream:</strong> {{ $student->stream->name ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Category:</strong> {{ $student->category->name ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>NEMIS Number:</strong> {{ $student->nemis_number ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>KNEC Assessment Number:</strong> {{ $student->knec_assessment_number ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    {{-- Parent Details --}}
    <div class="card mb-4">
        <div class="card-header">Parent/Guardian Details</div>
        <div class="card-body">
            @if ($student->parent)
                <div class="row">
                    <div class="col-md-6"><strong>Father's Name:</strong> {{ $student->parent->father_name ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Mother's Name:</strong> {{ $student->parent->mother_name ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Guardian's Name:</strong> {{ $student->parent->guardian_name ?? 'N/A' }}</div>
                </div>
            @else
                <p>No parent/guardian information available.</p>
            @endif
        </div>
    </div>

    {{-- Documents --}}
    <div class="card mb-4">
        <div class="card-header">Uploaded Documents</div>
        <div class="card-body">
            @if ($student->photo_path)
                <p><strong>Passport Photo:</strong> <a href="{{ asset('storage/' . $student->photo_path) }}" target="_blank">View Photo</a></p>
            @endif
            @if ($student->birth_certificate_path)
                <p><strong>Birth Certificate:</strong> <a href="{{ asset('storage/' . $student->birth_certificate_path) }}" target="_blank">View Certificate</a></p>
            @endif
            @if ($student->parent_id_card)
                <p><strong>Parent's ID:</strong> <a href="{{ asset('storage/' . $student->parent_id_card) }}" target="_blank">View Parent's ID</a></p>
            @endif
        </div>
    </div>

    {{-- Back Button --}}
    <a href="{{ route('students.index') }}" class="btn btn-primary">Back to Students</a>
</div>
@endsection

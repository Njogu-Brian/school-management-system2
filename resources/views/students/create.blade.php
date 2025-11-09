@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Admission</h1>

    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        {{-- Student Information --}}
        <h4>Student Information</h4>
        <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
        </div>
        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Gender</label>
            <select name="gender" class="form-control" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" name="dob" class="form-control">
        </div>

        {{-- Class and Category --}}
        <div class="mb-3">
            <label>Class</label>
            <select name="classroom_id" class="form-control" required>
                <option value="">Select Class</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Stream</label>
            <select name="stream_id" class="form-control">
                <option value="">Select Stream</option>
                @foreach ($streams as $stream)
                    <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <select name="category_id" class="form-control">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Family</label>
            <select name="family_id" class="form-control">
                <option value="">-- No linked family --</option>
                @foreach ($families as $family)
                    <option value="{{ $family->id }}">
                        {{ $family->guardian_name }} {{ $family->phone ? ' - '.$family->phone : '' }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Select an existing family to automatically link siblings and consolidate finance communications.</small>
        </div>

        {{-- Parent Info --}}
        <h4>Parent/Guardian Info</h4>
        <div class="mb-3">
            <label>Father's Name</label>
            <input type="text" name="father_name" class="form-control">
        </div>
        <div class="mb-3">
            <label>Father's Phone</label>
            <input type="text" name="father_phone" class="form-control">
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-success">Submit Admission</button>
    </form>
</div>
@endsection

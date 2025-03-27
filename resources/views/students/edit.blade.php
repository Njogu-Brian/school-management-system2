@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Student Information</h1>

    <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Student Information --}}
        <h4>Student Information</h4>
        <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="{{ $student->first_name }}" required>
        </div>

        <div class="mb-3">
            <label>Middle Name (Optional)</label>
            <input type="text" name="middle_name" class="form-control" value="{{ $student->middle_name }}">
        </div>

        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="{{ $student->last_name }}" required>
        </div>
        <div class="mb-3">
            <label>Gender</label>
            <select name="gender" class="form-control" required>
                <option value="Male" {{ $student->gender == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ $student->gender == 'Female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" name="dob" class="form-control" value="{{ $student->dob }}">
        </div>

        {{-- Class and Category --}}
        <div class="mb-3">
            <label>Class</label>
            <select id="classroom" name="classroom_id" class="form-control" required>
                <option value="">Select Class</option>
                @foreach ($classes as $classroom)
                    <option value="{{ $classroom->id }}" {{ isset($student) && $student->classroom_id == $classroom->id ? 'selected' : '' }}>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Stream (Optional)</label>
            <select id="stream" name="stream_id" class="form-control">
                <option value="">Select Stream (Optional)</option>
                @foreach ($streams as $stream)
                    <option value="{{ $stream->id }}" {{ isset($student) && $student->stream_id == $stream->id ? 'selected' : '' }}>
                        {{ $stream->name }}
                    </option>
                @endforeach
            </select>
        </div>


        <div class="mb-3">
            <label>Student Category</label>
            <select name="category_id" class="form-control">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ $student->category_id == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- NEMIS and KNEC --}}
        <div class="mb-3">
            <label>NEMIS Number</label>
            <input type="text" name="nemis_number" class="form-control" value="{{ $student->nemis_number }}">
        </div>
        <div class="mb-3">
            <label>KNEC Assessment Number</label>
            <input type="text" name="knec_assessment_number" class="form-control" value="{{ $student->knec_assessment_number }}">
        </div>

        {{-- Parent Information --}}
        <h4>Parent/Guardian Information</h4>
        <label>Link to Existing Parent</label>
        <select name="parent_id" class="form-control">
            <option value="">Select Parent (Optional)</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" {{ $student->parent_id == $parent->id ? 'selected' : '' }}>
                    {{ $parent->father_name }} / {{ $parent->mother_name }}
                </option>
            @endforeach
        </select>

        <hr>
        <label>Update or Add Parent Details</label>

        {{-- Father's Details --}}
        <h5>Father's Information</h5>
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="father_name" class="form-control" value="{{ optional($student->parent)->father_name }}">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="father_phone" class="form-control" value="{{ optional($student->parent)->father_phone }}">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="father_email" class="form-control" value="{{ optional($student->parent)->father_email }}">
        </div>
        <div class="mb-3">
            <label>ID Number</label>
            <input type="text" name="father_id_number" class="form-control" value="{{ optional($student->parent)->father_id_number }}">
        </div>

        {{-- Mother's Details --}}
        <h5>Mother's Information</h5>
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="mother_name" class="form-control" value="{{ optional($student->parent)->mother_name }}">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="mother_phone" class="form-control" value="{{ optional($student->parent)->mother_phone }}">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="mother_email" class="form-control" value="{{ optional($student->parent)->mother_email }}">
        </div>
        <div class="mb-3">
            <label>ID Number</label>
            <input type="text" name="mother_id_number" class="form-control" value="{{ optional($student->parent)->mother_id_number }}">
        </div>

        {{-- Document Uploads --}}
        <h4>Document Uploads (Leave blank to retain current files)</h4>
        <div class="mb-3">
            <label>Passport Photo</label>
            <input type="file" name="passport_photo" class="form-control">
        </div>
        <div class="mb-3">
            <label>Birth Certificate</label>
            <input type="file" name="birth_certificate" class="form-control">
        </div>
        <div class="mb-3">
            <label>Parent's ID (PDF or Image)</label>
            <input type="file" name="parent_id_card" class="form-control">
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary">Update Student</button>
    </form>
</div>
@endsection

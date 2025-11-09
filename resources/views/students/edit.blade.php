@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Student</h1>

    <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" value="{{ $student->first_name }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" value="{{ $student->last_name }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Class</label>
            <select name="classroom_id" class="form-control">
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" {{ $student->classroom_id == $classroom->id ? 'selected' : '' }}>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection

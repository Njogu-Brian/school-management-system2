@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Classroom</h1>

    <form action="{{ route('classrooms.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Class Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Assign Teachers</label>
            <select name="teacher_ids[]" class="form-control" multiple>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Add Classroom</button>
    </form>
</div>
@endsection

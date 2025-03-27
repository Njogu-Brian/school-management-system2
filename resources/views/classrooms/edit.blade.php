@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Classroom</h1>

    <form action="{{ route('classrooms.update', $classroom->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Class Name</label>
            <input type="text" name="name" class="form-control" value="{{ $classroom->name }}" required>
        </div>

        <div class="mb-3">
            <label>Assign Teacher</label>
            <select name="teacher_id" class="form-control">
                <option value="">No Teacher Assigned</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ $classroom->teacher_id == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->first_name }} {{ $teacher->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Classroom</button>
    </form>
</div>
@endsection

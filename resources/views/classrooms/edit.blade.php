@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Classroom</h1>

    <form action="{{ route('classrooms.update', $classroom->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Class Name</label>
            <input type="text" class="form-control" name="name" value="{{ old('name', $classroom->name) }}" required>
        </div>

        <div class="mb-3">
            <label>Assign Teachers</label>
            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                @forelse ($teachers as $teacher)
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="teacher_ids[]"
                            value="{{ $teacher->id }}"
                            class="form-check-input"
                            id="teacher_{{ $teacher->id }}"
                            {{ in_array($teacher->id, $assignedTeachers) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="teacher_{{ $teacher->id }}">
                            {{ $teacher->name }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No teachers found.</p>
                @endforelse
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Classroom</button>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Classroom</h1>

    <form action="{{ route('academics.classrooms.update', $classroom->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Class Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ old('name', $classroom->name) }}" required>
        </div>

        <div class="mb-3">
            <label>Next Class (for promotion)</label>
            <select name="next_class_id" class="form-control">
                <option value="">-- Select Next Class --</option>
                @if($classroom->is_alumni)
                    <option value="" selected>Alumni (Last Class)</option>
                @endif
                @foreach ($classrooms as $class)
                    <option value="{{ $class->id }}" @selected(old('next_class_id', $classroom->next_class_id) == $class->id)>
                        {{ $class->name }}
                        @if($class->is_alumni)
                            (Alumni)
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Select the class students will be promoted to. Leave empty or select "Alumni" for the last class.</small>
            @if($classroom->nextClass)
                <div class="mt-1">
                    <small class="text-info">Current next class: <strong>{{ $classroom->nextClass->name }}</strong></small>
                </div>
            @endif
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="is_beginner" value="1" class="form-check-input" id="is_beginner" 
                        @checked(old('is_beginner', $classroom->is_beginner))>
                    <label class="form-check-label" for="is_beginner">
                        Beginner Class (First Class)
                    </label>
                    <small class="text-muted d-block">Check if this is the entry/first class for new students</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="is_alumni" value="1" class="form-check-input" id="is_alumni"
                        @checked(old('is_alumni', $classroom->is_alumni))>
                    <label class="form-check-label" for="is_alumni">
                        Alumni Class (Last Class)
                    </label>
                    <small class="text-muted d-block">Check if this is the final class (students become alumni after this)</small>
                </div>
            </div>
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
        <a href="{{ route('academics.classrooms.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection

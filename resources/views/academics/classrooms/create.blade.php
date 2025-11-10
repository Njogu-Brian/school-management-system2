@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Classroom</h1>

    <form action="{{ route('academics.classrooms.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Class Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Next Class (for promotion)</label>
            <select name="next_class_id" class="form-control">
                <option value="">-- Select Next Class --</option>
                <option value="">Alumni (Last Class)</option>
                @foreach ($classrooms as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Select the class students will be promoted to. Leave empty or select "Alumni" for the last class.</small>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="is_beginner" value="1" class="form-check-input" id="is_beginner">
                    <label class="form-check-label" for="is_beginner">
                        Beginner Class (First Class)
                    </label>
                    <small class="text-muted d-block">Check if this is the entry/first class for new students</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="is_alumni" value="1" class="form-check-input" id="is_alumni">
                    <label class="form-check-label" for="is_alumni">
                        Alumni Class (Last Class)
                    </label>
                    <small class="text-muted d-block">Check if this is the final class (students become alumni after this)</small>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label>Assign Teachers</label>
            <select name="teacher_ids[]" class="form-control" multiple>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers</small>
        </div>

        <button type="submit" class="btn btn-success">Add Classroom</button>
        <a href="{{ route('academics.classrooms.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection

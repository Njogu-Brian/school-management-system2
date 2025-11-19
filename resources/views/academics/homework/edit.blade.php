@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Homework</h1>

    <form method="POST" action="{{ route('academics.homework.update', $homework) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="{{ $homework->title }}" required>
        </div>

        <div class="mb-3">
            <label>Instructions</label>
            <textarea name="instructions" class="form-control" rows="3">{{ $homework->instructions }}</textarea>
        </div>

        <div class="mb-3">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ $homework->due_date->format('Y-m-d') }}" required>
        </div>

        <div class="mb-3">
            <label>Target Scope</label>
            <select name="target_scope" id="target_scope" class="form-control" required>
                <option value="class" {{ $homework->target_scope == 'class' ? 'selected' : '' }}>Class</option>
                <option value="stream" {{ $homework->target_scope == 'stream' ? 'selected' : '' }}>Stream</option>
                <option value="students" {{ $homework->target_scope == 'students' ? 'selected' : '' }}>Specific Students</option>
            </select>
        </div>

        <div class="mb-3 scope-field scope-class">
            <label>Classroom</label>
            <select name="classroom_id" class="form-control">
                <option value="">-- Select Class --</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" {{ $homework->classroom_id == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3 scope-field scope-stream d-none">
            <label>Stream</label>
            <select name="stream_id" class="form-control">
                <option value="">-- Select Stream --</option>
                @foreach($classrooms as $classroom)
                    @foreach($classroom->streams as $stream)
                        <option value="{{ $stream->id }}" {{ $homework->stream_id == $stream->id ? 'selected' : '' }}>
                            {{ $classroom->name }} - {{ $stream->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Subject</label>
            <select name="subject_id" class="form-control">
                <option value="">-- Select Subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ $homework->subject_id == $subject->id ? 'selected' : '' }}>
                        {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3 scope-field scope-students d-none">
            <label>Students</label>
            <select name="student_ids[]" class="form-control" multiple>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" 
                        {{ $homework->students->pluck('id')->contains($student->id) ? 'selected' : '' }}>
                        {{ $student->admission_no }} - {{ $student->first_name }} {{ $student->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Attachment (optional)</label>
            <input type="file" name="attachment" class="form-control">
            @if($homework->file_path)
                <small>Current file: <a href="{{ asset('storage/'.$homework->file_path) }}" target="_blank">View</a></small>
            @endif
        </div>

        <button class="btn btn-success">Update</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scopeSelect = document.getElementById('target_scope');
        const scopeFields = document.querySelectorAll('.scope-field');

        function toggleScopeFields() {
            scopeFields.forEach(f => f.classList.add('d-none'));
            const selected = scopeSelect.value;
            document.querySelectorAll('.scope-' + selected).forEach(f => f.classList.remove('d-none'));
        }

        scopeSelect.addEventListener('change', toggleScopeFields);
        toggleScopeFields();
    });
</script>
@endsection

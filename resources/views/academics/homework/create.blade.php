@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Assign Homework</h1>

    <form method="POST" action="{{ route('academics.homework.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Instructions</label>
            <textarea name="instructions" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Target Scope</label>
            <select name="target_scope" id="target_scope" class="form-control" required>
                <option value="class">Class</option>
                <option value="stream">Stream</option>
                <option value="students">Specific Students</option>
                <option value="school">Entire School</option>
            </select>
        </div>

        <div class="mb-3 scope-field scope-class">
            <label>Classroom</label>
            <select name="classroom_id" class="form-control">
                <option value="">-- Select Class --</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3 scope-field scope-stream d-none">
            <label>Stream</label>
            <select name="stream_id" class="form-control">
                <option value="">-- Select Stream --</option>
                @foreach($classrooms as $classroom)
                    @foreach($classroom->streams as $stream)
                        <option value="{{ $stream->id }}">{{ $classroom->name }} - {{ $stream->name }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Subject</label>
            <select name="subject_id" class="form-control">
                <option value="">-- Select Subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3 scope-field scope-students d-none">
            <label>Students</label>
            <select name="student_ids[]" class="form-control" multiple>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->admission_number }} - {{ $student->first_name }} {{ $student->last_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Attachment (optional)</label>
            <input type="file" name="attachment" class="form-control">
        </div>

        <button class="btn btn-success">Assign</button>
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

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
            <label>Classroom</label>
            <select name="classroom_id" class="form-control" required>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Subject</label>
            <select name="subject_id" class="form-control" required>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Instructions</label>
            <textarea name="instructions" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Attach File (optional)</label>
            <input type="file" name="file" class="form-control">
        </div>

        <button class="btn btn-success">Assign</button>
    </form>
</div>
@endsection

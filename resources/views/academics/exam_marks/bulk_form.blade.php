@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Bulk Marks Entry</h1>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('academics.exam-marks.bulk.edit') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="exam_id" class="form-label">Exam</label>
            <select name="exam_id" class="form-select" required>
                <option value="">-- Select Exam --</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="classroom_id" class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select" required>
                <option value="">-- Select Class --</option>
                @foreach($classrooms as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="subject_id" class="form-label">Subject</label>
            <select name="subject_id" class="form-select" required>
                <option value="">-- Select Subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Proceed to Marks Entry</button>
    </form>
</div>
@endsection

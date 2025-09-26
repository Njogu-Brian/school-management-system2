@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Generate Report Card</h1>

    <form method="POST" action="{{ route('report-cards.store') }}">
        @csrf

        <div class="mb-3">
            <label>Student</label>
            <select name="student_id" class="form-control" required>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->admission_number }})</option>
                @endforeach
            </select>
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
            <label>Term</label>
            <select name="term_id" class="form-control" required>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Academic Year</label>
            <select name="academic_year_id" class="form-control" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->year }}</option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-success">Generate</button>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Record Student Behaviour</h1>

    <form action="{{ route('academics.student-behaviours.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Student</label>
            <select name="student_id" class="form-select" required>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->classrooms->name ?? '' }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Behaviour</label>
            <select name="behaviour_id" class="form-select" required>
                @foreach($behaviours as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Term</label>
            <select name="term_id" class="form-select" required>
                @foreach($terms as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
                @foreach($years as $y)
                    <option value="{{ $y->id }}">{{ $y->year }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection

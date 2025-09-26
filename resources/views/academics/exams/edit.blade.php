@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam</h1>

    <form method="POST" action="{{ route('exams.update',$exam) }}">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" value="{{ $exam->title }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Classroom</label>
            <select name="classroom_id" class="form-control">
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" {{ $exam->classroom_id == $classroom->id ? 'selected' : '' }}>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Term</label>
            <select name="term_id" class="form-control">
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ $exam->term_id == $term->id ? 'selected' : '' }}>
                        {{ $term->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Year</label>
            <select name="academic_year_id" class="form-control">
                @foreach($years as $year)
                    <option value="{{ $year->id }}" {{ $exam->academic_year_id == $year->id ? 'selected' : '' }}>
                        {{ $year->year }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="date" value="{{ $exam->date }}" class="form-control">
        </div>

        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection

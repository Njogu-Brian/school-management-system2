@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Exam</h1>

    <form method="POST" action="{{ route('academics.exams.store') }}">
        @csrf
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="row">
            <div class="col-md-3">
                <label>Category</label>
                <select name="type" class="form-select" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Modality</label>
                <select name="modality" class="form-select" required>
                    @foreach($modalities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Year</label>
                <select name="academic_year_id" class="form-select" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Term</label>
                <select name="term_id" class="form-select" required>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label>Classrooms</label>
            <select name="classrooms[]" class="form-control" multiple required>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Subjects</label>
            <select name="subjects[]" class="form-control" multiple required>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label>Starts On</label>
                <input type="datetime-local" name="starts_on" class="form-control">
            </div>
            <div class="col-md-6">
                <label>Ends On</label>
                <input type="datetime-local" name="ends_on" class="form-control">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <label>Max Marks</label>
                <input type="number" name="max_marks" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>Weight (%)</label>
                <input type="number" name="weight" class="form-control" required>
            </div>
        </div>

        <button class="btn btn-success mt-3">Save</button>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam</h1>

    <form method="POST" action="{{ route('academics.exams.update', $exam) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="name" value="{{ $exam->name }}" class="form-control" required>
        </div>

        <div class="row">
            <div class="col-md-3">
                <label>Category</label>
                <select name="type" class="form-select" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ $exam->type == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Modality</label>
                <select name="modality" class="form-select" required>
                    @foreach($modalities as $value => $label)
                        <option value="{{ $value }}" {{ $exam->modality == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Year</label>
                <select name="academic_year_id" class="form-select" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" {{ $exam->academic_year_id == $year->id ? 'selected' : '' }}>
                            {{ $year->year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Term</label>
                <select name="term_id" class="form-select" required>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ $exam->term_id == $term->id ? 'selected' : '' }}>
                            {{ $term->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label>Classrooms</label>
            <select name="classrooms[]" class="form-control" multiple required>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" {{ in_array($classroom->id, $selectedClassrooms) ? 'selected' : '' }}>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Subjects</label>
            <select name="subjects[]" class="form-control" multiple required>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ in_array($subject->id, $selectedSubjects) ? 'selected' : '' }}>
                        {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label>Starts On</label>
                <input type="datetime-local" name="starts_on" value="{{ $exam->starts_on?->format('Y-m-d\TH:i') }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label>Ends On</label>
                <input type="datetime-local" name="ends_on" value="{{ $exam->ends_on?->format('Y-m-d\TH:i') }}" class="form-control">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <label>Max Marks</label>
                <input type="number" name="max_marks" value="{{ $exam->max_marks }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>Weight (%)</label>
                <input type="number" name="weight" value="{{ $exam->weight }}" class="form-control" required>
            </div>
        </div>

        <button class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection

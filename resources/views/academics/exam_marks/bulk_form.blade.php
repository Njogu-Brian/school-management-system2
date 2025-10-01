@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Bulk Entry - Select Exam, Class & Subject</h1>

    <form action="{{ route('academics.exam-marks.bulk.edit') }}" method="POST">
        @csrf
        <div class="row mb-3">
            <div class="col-md-4">
                <label>Exam</label>
                <select name="exam_id" class="form-select" required>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->name }} - {{ $exam->term->name ?? '' }}/{{ $exam->academicYear->year ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label>Classroom</label>
                <select name="classroom_id" class="form-select" required>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label>Subject</label>
                <select name="subject_id" class="form-select" required>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button class="btn btn-success">Proceed</button>
    </form>
</div>
@endsection

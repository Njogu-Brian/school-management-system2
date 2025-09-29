@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Edit Exam Grade</h1>
    <form action="{{ route('academics.exam-grades.update',$exam_grade) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3"><label>Exam Type*</label>
            <input type="text" name="exam_type" value="{{ $exam_grade->exam_type }}" class="form-control" required>
        </div>
        <div class="mb-3"><label>Grade Name*</label>
            <input type="text" name="grade_name" value="{{ $exam_grade->grade_name }}" class="form-control" required>
        </div>
        <div class="mb-3"><label>Percent From*</label>
            <input type="number" step="0.01" name="percent_from" value="{{ $exam_grade->percent_from }}" class="form-control" required>
        </div>
        <div class="mb-3"><label>Percent Upto*</label>
            <input type="number" step="0.01" name="percent_upto" value="{{ $exam_grade->percent_upto }}" class="form-control" required>
        </div>
        <div class="mb-3"><label>Grade Point</label>
            <input type="number" step="0.01" name="grade_point" value="{{ $exam_grade->grade_point }}" class="form-control">
        </div>
        <div class="mb-3"><label>Description</label>
            <textarea name="description" class="form-control">{{ $exam_grade->description }}</textarea>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection

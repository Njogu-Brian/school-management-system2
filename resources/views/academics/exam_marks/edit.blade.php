@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam Mark</h1>

    <form action="{{ route('academics.exam-marks.update',$exam_mark) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Score Raw</label>
            <input type="number" name="score_raw" step="0.01" class="form-control"
                   value="{{ old('score_raw',$exam_mark->score_raw) }}">
        </div>
        <div class="mb-3">
            <label>Opener</label>
            <input type="number" name="opener_score" step="0.01" class="form-control"
                   value="{{ old('opener_score',$exam_mark->opener_score) }}">
        </div>
        <div class="mb-3">
            <label>Midterm</label>
            <input type="number" name="midterm_score" step="0.01" class="form-control"
                   value="{{ old('midterm_score',$exam_mark->midterm_score) }}">
        </div>
        <div class="mb-3">
            <label>Endterm</label>
            <input type="number" name="endterm_score" step="0.01" class="form-control"
                   value="{{ old('endterm_score',$exam_mark->endterm_score) }}">
        </div>
        <div class="mb-3">
            <label>Remark</label>
            <input type="text" name="remark" class="form-control" 
                   value="{{ old('remark',$exam_mark->remark) }}">
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection

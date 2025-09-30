@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Exam Marks</h1>

    <form method="POST" action="{{ route('academics.exam-marks.update', $exam_mark) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Student</label>
            <input type="text" class="form-control" value="{{ $exam_mark->student->full_name }}" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" class="form-control" value="{{ $exam_mark->subject->name }}" disabled>
        </div>

        <div class="row">
            <div class="col">
                <label class="form-label">Opener Score</label>
                <input type="number" step="0.01" name="opener_score" id="opener_score"
                    class="form-control"
                    value="{{ old('opener_score', $exam_mark->opener_score) }}">
            </div>
            <div class="col">
                <label class="form-label">Midterm Score</label>
                <input type="number" step="0.01" name="midterm_score" id="midterm_score"
                    class="form-control"
                    value="{{ old('midterm_score', $exam_mark->midterm_score) }}">
            </div>
            <div class="col">
                <label class="form-label">Endterm Score</label>
                <input type="number" step="0.01" name="endterm_score" id="endterm_score"
                    class="form-control"
                    value="{{ old('endterm_score', $exam_mark->endterm_score) }}">
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label">Final Score (Auto Average)</label>
            <input type="number" step="0.01" name="score_raw" id="score_raw" class="form-control"
                value="{{ old('score_raw', $exam_mark->score_raw) }}" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Grade (Auto)</label>
            <input type="text" class="form-control" id="grade_label"
                value="{{ old('grade_label', $exam_mark->grade_label) }}" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Subject Remark</label>
            <textarea name="subject_remark" class="form-control">{{ old('subject_remark', $exam_mark->subject_remark) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">General Remark</label>
            <textarea name="remark" class="form-control">{{ old('remark', $exam_mark->remark) }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">💾 Save Changes</button>
        <a href="{{ route('academics.exam-marks.index', ['exam_id'=>$exam_mark->exam_id]) }}" class="btn btn-secondary">⬅ Back</a>
    </form>
</div>

{{-- Auto Average + Grade Calculation --}}
<script>
function calculateGrade() {
    const opener  = parseFloat(document.getElementById('opener_score').value) || 0;
    const midterm = parseFloat(document.getElementById('midterm_score').value) || 0;
    const endterm = parseFloat(document.getElementById('endterm_score').value) || 0;

    let scores = [opener, midterm, endterm].filter(v => v > 0);
    let average = scores.length > 0 ? (scores.reduce((a,b) => a+b, 0) / scores.length) : 0;

    document.getElementById('score_raw').value = average.toFixed(2);

    let grade = "BE"; // default
    if (average >= 80) grade = "EE";   // Exceeding Expectation
    else if (average >= 60) grade = "ME"; // Meeting Expectation
    else if (average >= 30) grade = "AE"; // Approaching Expectation
    else grade = "BE"; // Below Expectation

    document.getElementById('grade_label').value = grade;
}

document.querySelectorAll("#opener_score, #midterm_score, #endterm_score").forEach(el => {
    el.addEventListener("input", calculateGrade);
});

calculateGrade();
</script>
@endsection

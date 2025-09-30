@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Bulk Edit Exam Marks</h1>

    <form method="POST" action="{{ route('academics.exam-marks.bulk.store') }}">
        @csrf

        <input type="hidden" name="exam_id" value="{{ $exam->id }}">
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">

        <div class="mb-3">
            <strong>Exam:</strong> {{ $exam->name }} ({{ $exam->type }})<br>
            <strong>Classroom:</strong> {{ $class->name }}<br>
            <strong>Subject:</strong> {{ $subject->name }}
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Opener</th>
                        <th>Midterm</th>
                        <th>Endterm</th>
                        <th>Final Avg</th>
                        <th>Grade</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($students as $student)
                    @php $mark = $existing[$student->id] ?? null; @endphp
                    <tr>
                        <td>{{ $student->full_name }}
                            <input type="hidden" name="rows[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                        </td>
                        <td><input type="number" name="rows[{{ $student->id }}][opener_score]" class="form-control score-input" min="0" max="100" value="{{ $mark->opener_score ?? '' }}"></td>
                        <td><input type="number" name="rows[{{ $student->id }}][midterm_score]" class="form-control score-input" min="0" max="100" value="{{ $mark->midterm_score ?? '' }}"></td>
                        <td><input type="number" name="rows[{{ $student->id }}][endterm_score]" class="form-control score-input" min="0" max="100" value="{{ $mark->endterm_score ?? '' }}"></td>
                        <td><input type="text" name="rows[{{ $student->id }}][score_raw]" class="form-control final-score" readonly value="{{ $mark->score_raw ?? '' }}"></td>
                        <td><input type="text" class="form-control final-grade" readonly value="{{ $mark->grade_label ?? '' }}"></td>
                        <td><input type="text" name="rows[{{ $student->id }}][subject_remark]" class="form-control" value="{{ $mark->subject_remark ?? '' }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Marks</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
function calculateAverage(row) {
    let opener  = parseFloat(row.querySelector('[name*="[opener_score]"]').value)  || 0;
    let midterm = parseFloat(row.querySelector('[name*="[midterm_score]"]').value) || 0;
    let endterm = parseFloat(row.querySelector('[name*="[endterm_score]"]').value) || 0;

    let scores = [opener, midterm, endterm].filter(v => v > 0);
    let avg = scores.length ? (scores.reduce((a,b) => a+b, 0) / scores.length) : '';

    row.querySelector('.final-score').value = avg ? avg.toFixed(2) : '';

    let grade = '';
    if (avg >= 80) grade = 'EE';
    else if (avg >= 60) grade = 'ME';
    else if (avg >= 30) grade = 'AE';
    else if (avg >= 0)  grade = 'BE';

    row.querySelector('.final-grade').value = grade;
}

document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', function() {
        calculateAverage(this.closest('tr'));
    });
});
</script>
@endpush

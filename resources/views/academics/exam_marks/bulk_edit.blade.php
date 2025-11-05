@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">
        Bulk Entry for {{ $exam->name }} — {{ $class->name }} — {{ $subject->name }}
    </h1>

    <form action="{{ route('academics.exam-marks.bulk.store') }}" method="POST" id="bulkMarksForm">
        @csrf
            <input type="hidden" name="exam_id" value="{{ $exam->id }}">
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <input type="hidden" name="classroom_id" value="{{ $class->id }}">

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Student</th>
                        <th>Opener</th>
                        <th>Midterm</th>
                        <th>Endterm</th>
                        <th>Final (Auto Avg)</th>
                        <th>Grade</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $st)
                        @php $m = $existing[$st->id] ?? null; @endphp
                        <tr>
                            <td>{{ $st->full_name }}</td>
                            <td><input type="number" step="0.01" name="rows[{{ $loop->index }}][opener_score]" class="form-control score-input" value="{{ old("rows.$loop->index.opener_score", $m->opener_score ?? '') }}"></td>
                            <td><input type="number" step="0.01" name="rows[{{ $loop->index }}][midterm_score]" class="form-control score-input" value="{{ old("rows.$loop->index.midterm_score", $m->midterm_score ?? '') }}"></td>
                            <td><input type="number" step="0.01" name="rows[{{ $loop->index }}][endterm_score]" class="form-control score-input" value="{{ old("rows.$loop->index.endterm_score", $m->endterm_score ?? '') }}"></td>
                            <td><input type="number" step="0.01" class="form-control final-score" value="{{ $m->score_raw ?? '' }}" readonly></td>
                            <td>{{ $m->grade_label ?? '-' }}</td>
                            <td>
                                <input type="hidden" name="rows[{{ $loop->index }}][student_id]" value="{{ $st->id }}">
                                <input type="text" name="rows[{{ $loop->index }}][subject_remark]" class="form-control" value="{{ old("rows.$loop->index.subject_remark", $m->subject_remark ?? '') }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-end mt-4">
            <button type="submit" form="bulkMarksForm" class="btn btn-primary">
                Save All
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function computeRow(row){
    const opener  = parseFloat(row.querySelector('[name*="opener_score"]')?.value)  || 0;
    const midterm = parseFloat(row.querySelector('[name*="midterm_score"]')?.value) || 0;
    const endterm = parseFloat(row.querySelector('[name*="endterm_score"]')?.value) || 0;

    // Count non-empty numbers (allow zero as a valid value)
    const parts = [row.querySelector('[name*="opener_score"]')?.value,
                   row.querySelector('[name*="midterm_score"]')?.value,
                   row.querySelector('[name*="endterm_score"]')?.value]
                   .filter(v => v !== '' && v !== null);

    const avg = parts.length ? ((opener + midterm + endterm) / parts.length).toFixed(2) : '';
    const final = row.querySelector('.final-score');
    if (final) final.value = avg;
}

// Live recompute on user input
document.querySelectorAll('.score-input').forEach(inp => {
    inp.addEventListener('input', function(){
        computeRow(this.closest('tr'));
    });
});

// Compute once on page load for all rows
document.querySelectorAll('tbody tr').forEach(tr => computeRow(tr));
</script>
@endpush

@endsection

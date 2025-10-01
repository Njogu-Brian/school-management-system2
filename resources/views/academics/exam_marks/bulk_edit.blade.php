@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Bulk Entry for {{ $exam->name }} - {{ $class->name }} - {{ $subject->name }}</h1>

    <form action="{{ route('academics.exam-marks.bulk.store') }}" method="POST">
        @csrf
        <input type="hidden" name="exam_id" value="{{ $exam->id }}">
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Opener</th>
                        <th>Midterm</th>
                        <th>Endterm</th>
                        <th>Final (Auto Avg)</th>
                        <th>Rubric</th>
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
                            <td><input type="number" step="0.01" class="form-control final-score" readonly></td>
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
        <button class="btn btn-success mt-3">Save All</button>
    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('.score-input').forEach(inp => {
    inp.addEventListener('input', function(){
        let row = this.closest('tr');
        let opener = parseFloat(row.querySelector('[name*="opener_score"]').value) || 0;
        let midterm = parseFloat(row.querySelector('[name*="midterm_score"]').value) || 0;
        let endterm = parseFloat(row.querySelector('[name*="endterm_score"]').value) || 0;
        let count = [opener, midterm, endterm].filter(v => v > 0).length;
        let avg = count > 0 ? ((opener + midterm + endterm) / count).toFixed(2) : '';
        row.querySelector('.final-score').value = avg;
    });
});
</script>
@endpush
@endsection

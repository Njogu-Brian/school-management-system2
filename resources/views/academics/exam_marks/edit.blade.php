@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="mb-0">Edit Exam Mark</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Marks
            </a>
            @if($exam_mark->exam_id && $exam_mark->student?->classroom_id && $exam_mark->subject_id)
                <a
                    href="{{ route('academics.exam-marks.bulk.edit.view', [
                        'exam_id'      => $exam_mark->exam_id,
                        'classroom_id' => $exam_mark->student?->classroom_id,
                        'subject_id'   => $exam_mark->subject_id,
                    ]) }}"
                    class="btn btn-outline-primary"
                >
                    <i class="bi bi-table"></i> Open Bulk Editor
                </a>
            @endif
        </div>
    </div>

    {{-- Header card with context --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <h5 class="mb-1">
                        {{ $exam_mark->student->full_name ?? 'Unknown Student' }}
                        @if($exam_mark->student?->admission_number)
                            <small class="text-muted"> (Adm: {{ $exam_mark->student->admission_number }})</small>
                        @endif
                    </h5>
                    <div class="text-muted">
                        Class:
                        <span class="fw-semibold">{{ $exam_mark->student->classroom->name ?? 'N/A' }}</span>
                        @if($exam_mark->student?->stream)
                            <span class="ms-2">| Stream: <span class="fw-semibold">{{ $exam_mark->student->stream->name }}</span></span>
                        @endif
                        <span class="ms-2">| Subject: <span class="fw-semibold">{{ $exam_mark->subject->name ?? 'N/A' }}</span></span>
                        <span class="ms-2">| Exam: <span class="fw-semibold">{{ $exam_mark->exam->name ?? 'N/A' }}</span></span>
                    </div>
                </div>
                <div class="text-end">
                    @if($exam_mark->status)
                        <span class="badge bg-secondary">{{ ucfirst($exam_mark->status) }}</span>
                    @endif
                    @if($exam_mark->grade_label)
                        <span class="badge bg-dark ms-1">Grade: {{ $exam_mark->grade_label }}</span>
                    @endif
                    @if(!is_null($exam_mark->pl_level))
                        <span class="badge bg-info text-dark ms-1">PL: {{ $exam_mark->pl_level }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Update form --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <form id="editMarkForm" action="{{ route('academics.exam-marks.update', $exam_mark) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Opener</label>
                        <input
                            type="number"
                            step="0.01"
                            name="opener_score"
                            class="form-control score-input @error('opener_score') is-invalid @enderror"
                            value="{{ old('opener_score', $exam_mark->opener_score) }}"
                            placeholder="e.g. 45"
                        >
                        @error('opener_score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Midterm</label>
                        <input
                            type="number"
                            step="0.01"
                            name="midterm_score"
                            class="form-control score-input @error('midterm_score') is-invalid @enderror"
                            value="{{ old('midterm_score', $exam_mark->midterm_score) }}"
                            placeholder="e.g. 70"
                        >
                        @error('midterm_score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Endterm</label>
                        <input
                            type="number"
                            step="0.01"
                            name="endterm_score"
                            class="form-control score-input @error('endterm_score') is-invalid @enderror"
                            value="{{ old('endterm_score', $exam_mark->endterm_score) }}"
                            placeholder="e.g. 82"
                        >
                        @error('endterm_score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Final (Auto Avg)</label>
                        <input
                            type="number"
                            step="0.01"
                            class="form-control"
                            id="finalScorePreview"
                            value="{{ old('score_raw', $exam_mark->score_raw) }}"
                            readonly
                        >
                        <div class="form-text">Preview only — saved on server automatically.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Subject Remark</label>
                        <input
                            type="text"
                            name="subject_remark"
                            class="form-control @error('subject_remark') is-invalid @enderror"
                            value="{{ old('subject_remark', $exam_mark->subject_remark) }}"
                            placeholder="e.g. Excellent progress"
                        >
                        @error('subject_remark') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">General Remark</label>
                        <input
                            type="text"
                            name="remark"
                            class="form-control @error('remark') is-invalid @enderror"
                            value="{{ old('remark', $exam_mark->remark) }}"
                            placeholder="Optional comment"
                        >
                        @error('remark') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id]) }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const opener  = document.querySelector('input[name="opener_score"]');
    const midterm = document.querySelector('input[name="midterm_score"]');
    const endterm = document.querySelector('input[name="endterm_score"]');
    const final   = document.getElementById('finalScorePreview');

    function compute(){
        const vals = [
            opener?.value,
            midterm?.value,
            endterm?.value
        ];
        // Count only non-empty inputs; allow zero as valid
        const nums = vals
            .filter(v => v !== '' && v !== null)
            .map(v => parseFloat(v));

        if (nums.length === 0 || nums.some(isNaN)) {
            final.value = '';
            return;
        }
        const sum = nums.reduce((a,b)=>a+b, 0);
        final.value = (sum / nums.length).toFixed(2);
    }

    [opener, midterm, endterm].forEach(el => el && el.addEventListener('input', compute));

    // initial compute on load
    compute();
})();
</script>
@endpush

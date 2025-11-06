@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Enter Marks</h3>
      <small class="text-muted">
        {{ $exam->name }} • {{ $class->name }} • {{ $subject->name }}
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Change Selection
      </a>
      {{-- Optional CSV/Excel import (hook up if you added the import route) --}}
      {{-- 
      <form class="d-inline" method="post" action="{{ route('exams.results.import') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="exam_id" value="{{ $exam->id }}">
        <input type="hidden" name="classroom_id" value="{{ $class->id }}">
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
        <input type="file" name="file" class="form-control d-inline" style="width:240px" required>
        <button class="btn btn-outline-primary ms-2"><i class="bi bi-upload"></i> Import</button>
      </form>
      --}}
    </div>
  </div>

  @includeIf('partials.alerts')

  <form method="post" action="{{ route('academics.exam-marks.bulk.store') }}" class="card shadow-sm">
    @csrf
    <input type="hidden" name="exam_id" value="{{ $exam->id }}">
    <input type="hidden" name="classroom_id" value="{{ $class->id }}">
    <input type="hidden" name="subject_id" value="{{ $subject->id }}">

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px">#</th>
              <th>Student</th>
              <th style="width:160px" class="text-center">Score</th>
              <th>Remark</th>
            </tr>
          </thead>
          <tbody>
            @foreach($students as $i => $s)
              @php
                $m = $existing[$s->id] ?? null;
                $score = $m?->score_raw;
                $remark = $m?->subject_remark;
              @endphp
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                  <div class="fw-semibold">
                    {{ $s->full_name ?? ($s->first_name.' '.$s->last_name) }}
                  </div>
                  <div class="small text-muted">Adm: {{ $s->admission_number ?? '—' }}</div>
                  <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $s->id }}">
                </td>
                <td>
                  <input type="number" step="0.01" min="0" class="form-control text-center score-input"
                         name="rows[{{ $i }}][score]" value="{{ old("rows.$i.score", $score) }}"
                         placeholder="e.g. 64.5">
                </td>
                <td>
                  <input type="text" class="form-control" name="rows[{{ $i }}][subject_remark]"
                         value="{{ old("rows.$i.subject_remark", $remark) }}" maxlength="500"
                         placeholder="Optional remark">
                </td>
              </tr>
            @endforeach

            @if($students->isEmpty())
              <tr><td colspan="4" class="text-center text-muted py-4">No students in this class.</td></tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between">
      <div class="small text-muted">
        Scores are saved as <strong>score_raw</strong>. Grades auto-derive from the exam type’s bands.
      </div>
      <div>
        <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Save All</button>
      </div>
    </div>
  </form>
</div>

@push('scripts')
<script>
  // Simple numeric clamp (0..100 by default). Adjust if your max differs.
  document.querySelectorAll('.score-input').forEach(inp => {
    inp.addEventListener('change', () => {
      const v = inp.value.trim();
      if (v === '') return;
      let n = parseFloat(v);
      if (isNaN(n)) { inp.value = ''; return; }
      if (n < 0) n = 0;
      if (n > 100) n = 100;
      inp.value = n.toFixed(2);
    });
  });
</script>
@endpush
@endsection

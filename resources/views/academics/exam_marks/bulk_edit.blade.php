@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Enter Marks</h1>
        <p class="text-muted mb-0">{{ $exam->name }} • {{ $class->name }} • {{ $subject->name }}</p>
      </div>
      <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Change Selection</a>
    </div>

    @includeIf('partials.alerts')

    <form method="post" action="{{ route('academics.exam-marks.bulk.store') }}" class="settings-card">
      @csrf
      <input type="hidden" name="exam_id" value="{{ $exam->id }}">
      <input type="hidden" name="classroom_id" value="{{ $class->id }}">
      <input type="hidden" name="subject_id" value="{{ $subject->id }}">

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
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
                    <div class="fw-semibold">{{ $s->full_name }}</div>
                    <div class="small text-muted">Adm: {{ $s->admission_number ?? '—' }}</div>
                    <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $s->id }}">
                  </td>
                  <td>
                    <input type="number" step="0.01" min="0" class="form-control text-center score-input" name="rows[{{ $i }}][score]" value="{{ old("rows.$i.score", $score) }}" placeholder="e.g. 64.5">
                  </td>
                  <td>
                    <input type="text" class="form-control" name="rows[{{ $i }}][subject_remark]" value="{{ old("rows.$i.subject_remark", $remark) }}" maxlength="500" placeholder="Optional remark">
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

      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">Scores are saved as <strong>score_raw</strong>. Grades auto-derive from the exam type’s bands.</div>
        <button class="btn btn-settings-primary"><i class="bi bi-save2 me-1"></i>Save All</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
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

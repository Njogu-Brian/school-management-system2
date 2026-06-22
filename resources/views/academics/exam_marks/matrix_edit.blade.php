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
        <h1 class="mb-1">Enter Marks — Matrix</h1>
        <p class="text-muted mb-0">
          Type: {{ $examType->name }} · Class: {{ $classroom->name }}@if($stream) · Stream: {{ $stream->name }}@endif
        </p>
      </div>
      <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Change Selection</a>
    </div>

    @includeIf('partials.alerts')

    @if($exams->isEmpty())
      <div class="alert alert-warning">No open, marking, or under-review exams found for this exam type and class context.</div>
    @elseif($students->isEmpty())
      <div class="alert alert-warning">No active learners found for the selected class/stream context.</div>
    @endif

    <form method="post" action="{{ route('academics.exam-marks.matrix.store') }}" class="settings-card" id="matrix-form">
      @csrf
      <input type="hidden" name="exam_type_id" value="{{ $examType->id }}">
      <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
      @if($stream)
        <input type="hidden" name="stream_id" value="{{ $stream->id }}">
      @endif

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="min-width:220px;">Learner</th>
                @foreach($exams as $exam)
                  @php $meta = $examMeta[$exam->id] ?? ['can_edit' => false, 'status' => $exam->status]; @endphp
                  <th style="min-width:220px;" data-exam-id="{{ $exam->id }}">
                    <div class="fw-semibold">{{ $exam->name }}</div>
                    <div class="small text-muted">{{ $exam->subject?->name ?? 'Subject' }}</div>
                    <div class="small text-muted">Max {{ (float)($exam->examType?->default_max_mark ?? $exam->max_marks ?? 100) }}</div>
                    @if($meta['status'] === 'moderation')
                      <span class="badge bg-warning text-dark">Under review</span>
                    @endif
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse($students as $s)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $s->full_name }}</div>
                    <div class="small text-muted">Adm: {{ $s->admission_number ?? '—' }}</div>
                  </td>
                  @foreach($exams as $exam)
                    @php
                      $mark = $existing[$s->id.'-'.$exam->id] ?? null;
                      $meta = $examMeta[$exam->id] ?? ['can_edit' => false];
                    @endphp
                    <td>
                      <input
                        type="number"
                        step="0.01"
                        class="form-control form-control-sm mb-1 matrix-field"
                        data-student-id="{{ $s->id }}"
                        data-exam-id="{{ $exam->id }}"
                        data-field="score"
                        name="rows[{{ $s->id }}][{{ $exam->id }}][score]"
                        value="{{ old("rows.$s->id.$exam->id.score", $mark?->score_raw) }}"
                        placeholder="Score"
                        @disabled(!$meta['can_edit'])>
                      <input
                        type="text"
                        class="form-control form-control-sm matrix-field"
                        data-student-id="{{ $s->id }}"
                        data-exam-id="{{ $exam->id }}"
                        data-field="subject_remark"
                        name="rows[{{ $s->id }}][{{ $exam->id }}][subject_remark]"
                        value="{{ old("rows.$s->id.$exam->id.subject_remark", $mark?->subject_remark) }}"
                        maxlength="500"
                        placeholder="Remark (optional)"
                        @disabled(!$meta['can_edit'])>
                    </td>
                  @endforeach
                </tr>
              @empty
                <tr>
                  <td colspan="{{ 1 + $exams->count() }}" class="text-center text-muted py-4">No learners found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="small text-muted">Matrix entries autosave as draft. Submit individual exams when marking is complete.</div>
          <div id="matrix-autosave-status" class="small text-muted mt-1"></div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <button type="submit" class="btn btn-outline-secondary" @disabled($students->isEmpty() || $exams->isEmpty())>
            <i class="bi bi-save2 me-1"></i>Save draft
          </button>
          @foreach($exams as $exam)
            @php $meta = $examMeta[$exam->id] ?? ['can_edit' => false, 'status' => $exam->status]; @endphp
            @if($meta['can_edit'] && $meta['status'] !== 'moderation')
              <button type="submit" name="submit_for_review" value="1" class="btn btn-sm btn-settings-primary"
                onclick="document.getElementById('submit-exam-ids').value='{{ $exam->id }}'; return confirm('Submit {{ $exam->name }} for review? Teachers cannot edit after submission.');">
                Submit {{ Str::limit($exam->name, 20) }}
              </button>
            @endif
          @endforeach
          <input type="hidden" name="submit_exam_ids[]" id="submit-exam-ids" value="">
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const storeUrl = @json(route('academics.exam-marks.matrix.store'));
  const csrf = @json(csrf_token());
  const examTypeId = @json($examType->id);
  const classroomId = @json($classroom->id);
  const streamId = @json($stream?->id);
  const statusEl = document.getElementById('matrix-autosave-status');
  let timer = null;
  let saving = false;

  function collectPayload() {
    const rows = {};
    document.querySelectorAll('.matrix-field').forEach(el => {
      const sid = el.dataset.studentId;
      const eid = el.dataset.examId;
      const field = el.dataset.field;
      if (!rows[sid]) rows[sid] = {};
      if (!rows[sid][eid]) rows[sid][eid] = {};
      rows[sid][eid][field] = el.value.trim();
    });
    return rows;
  }

  async function autosave() {
    if (saving) return;
    saving = true;
    statusEl.textContent = 'Saving draft…';
    const body = new FormData();
    body.append('_token', csrf);
    body.append('exam_type_id', examTypeId);
    body.append('classroom_id', classroomId);
    if (streamId) body.append('stream_id', streamId);
    const rows = collectPayload();
    Object.entries(rows).forEach(([sid, exams]) => {
      Object.entries(exams).forEach(([eid, payload]) => {
        if (payload.score) body.append(`rows[${sid}][${eid}][score]`, payload.score);
        if (payload.subject_remark) body.append(`rows[${sid}][${eid}][subject_remark]`, payload.subject_remark);
      });
    });

    try {
      const res = await fetch(storeUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body,
      });
      const data = await res.json();
      if (data.success) {
        statusEl.textContent = `Draft saved · ${new Date().toLocaleTimeString()}`;
      } else {
        statusEl.textContent = data.message || 'Autosave failed';
      }
    } catch (e) {
      statusEl.textContent = 'Autosave failed — check connection';
    } finally {
      saving = false;
    }
  }

  function scheduleAutosave() {
    clearTimeout(timer);
    timer = setTimeout(autosave, 1500);
  }

  document.querySelectorAll('.matrix-field:not([disabled])').forEach(el => {
    el.addEventListener('input', scheduleAutosave);
    el.addEventListener('change', scheduleAutosave);
  });
})();
</script>
@endpush
@endsection

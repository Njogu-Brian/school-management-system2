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

    @if(!empty($examAudits) && $exams->isNotEmpty())
      <div class="settings-card mb-3">
        <div class="card-body">
          <h2 class="h6 mb-2">Exam submission status</h2>
          <div class="d-flex flex-wrap gap-2">
            @foreach($exams as $exam)
              @php $audit = $examAudits[$exam->id] ?? null; @endphp
              <div class="border rounded px-3 py-2 small">
                <div class="fw-semibold">{{ $exam->name }}</div>
                @if(!empty($audit['marking_submitted_at']))
                  <div class="text-muted">Submitted {{ \Carbon\Carbon::parse($audit['marking_submitted_at'])->format('d M Y H:i') }}</div>
                  <div>By {{ $audit['marking_submitted_by'] ?? '—' }}</div>
                @else
                  <div class="text-muted">Not submitted yet</div>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

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
                      $isAbsent = (bool) old("rows.$s->id.$exam->id.is_absent", $mark?->is_absent ?? false);
                    @endphp
                    <td>
                      <div class="form-check form-check-inline mb-1">
                        <input
                          type="checkbox"
                          class="form-check-input matrix-absent matrix-field"
                          data-student-id="{{ $s->id }}"
                          data-exam-id="{{ $exam->id }}"
                          data-field="is_absent"
                          name="rows[{{ $s->id }}][{{ $exam->id }}][is_absent]"
                          value="1"
                          @checked($isAbsent)
                          @disabled(!$meta['can_edit'])>
                        <label class="form-check-label small">Absent</label>
                      </div>
                      <input
                        type="number"
                        step="0.01"
                        class="form-control form-control-sm mb-1 matrix-field matrix-score"
                        data-student-id="{{ $s->id }}"
                        data-exam-id="{{ $exam->id }}"
                        data-field="score"
                        name="rows[{{ $s->id }}][{{ $exam->id }}][score]"
                        value="{{ old("rows.$s->id.$exam->id.score", $isAbsent ? '' : $mark?->score_raw) }}"
                        placeholder="Score"
                        @disabled(!$meta['can_edit'] || $isAbsent)>
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
          <div class="small text-muted">Matrix entries autosave as draft. Mark absent students to exclude them from mean score.</div>
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
      if (field === 'is_absent') {
        rows[sid][eid][field] = el.checked ? '1' : '0';
      } else {
        rows[sid][eid][field] = el.value.trim();
      }
    });
    return rows;
  }

  document.querySelectorAll('.matrix-absent').forEach(box => {
    box.addEventListener('change', () => {
      const sid = box.dataset.studentId;
      const eid = box.dataset.examId;
      const score = document.querySelector(`.matrix-score[data-student-id="${sid}"][data-exam-id="${eid}"]`);
      if (!score) return;
      if (box.checked) {
        score.value = '';
        score.disabled = true;
      } else {
        score.disabled = false;
      }
      scheduleAutosave();
    });
  });

  document.querySelectorAll('.matrix-score').forEach(inp => {
    inp.addEventListener('input', () => {
      const sid = inp.dataset.studentId;
      const eid = inp.dataset.examId;
      const absent = document.querySelector(`.matrix-absent[data-student-id="${sid}"][data-exam-id="${eid}"]`);
      if (inp.value.trim() !== '' && absent) absent.checked = false;
    });
  });

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
        if (payload.is_absent === '1') body.append(`rows[${sid}][${eid}][is_absent]`, '1');
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

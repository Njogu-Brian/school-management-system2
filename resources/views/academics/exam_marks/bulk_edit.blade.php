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
        @if(!$canEdit)
          <span class="badge bg-warning text-dark mt-2">Under review — read only</span>
        @elseif($exam->status === 'moderation')
          <span class="badge bg-info mt-2">Under review — you can edit as Senior Teacher / Admin</span>
        @else
          <span class="badge bg-secondary mt-2">Draft autosave enabled</span>
        @endif
      </div>
      <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Change Selection</a>
    </div>

    @includeIf('partials.alerts')

    @include('academics.exam_marks.partials.entry_audit', ['entryAudit' => $entryAudit ?? null])

    <form method="post" action="{{ route('academics.exam-marks.bulk.store') }}" class="settings-card" id="marks-form">
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
                <th style="width:100px" class="text-center">Absent</th>
                <th style="width:160px" class="text-center">Score</th>
                <th>Remark</th>
              </tr>
            </thead>
            <tbody>
              @foreach($students as $i => $s)
                @php
                  $m = $existing[$s->id] ?? null;
                  $score = $m?->is_absent ? null : $m?->score_raw;
                  $remark = $m?->subject_remark;
                  $isAbsent = (bool) old("rows.$i.is_absent", $m?->is_absent ?? false);
                @endphp
                <tr data-row-index="{{ $i }}">
                  <td>{{ $loop->iteration }}</td>
                  <td>
                    <div class="fw-semibold">{{ $s->full_name }}</div>
                    <div class="small text-muted">Adm: {{ $s->admission_number ?? '—' }}</div>
                    <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $s->id }}" data-student-id="{{ $s->id }}">
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="form-check-input absent-toggle mark-field" name="rows[{{ $i }}][is_absent]" value="1" @checked($isAbsent) @disabled(!$canEdit)>
                  </td>
                  <td>
                    <input type="number" step="0.01" min="0" class="form-control text-center score-input mark-field" name="rows[{{ $i }}][score]" value="{{ old("rows.$i.score", $score) }}" placeholder="e.g. 64.5" @disabled(!$canEdit || $isAbsent)>
                  </td>
                  <td>
                    <input type="text" class="form-control mark-field" name="rows[{{ $i }}][subject_remark]" value="{{ old("rows.$i.subject_remark", $remark) }}" maxlength="500" placeholder="Optional remark" @disabled(!$canEdit)>
                  </td>
                </tr>
              @endforeach
              @if($students->isEmpty())
                <tr><td colspan="5" class="text-center text-muted py-4">No students in this class.</td></tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="small text-muted">Marks autosave as you type. Mark <strong>Absent</strong> for students who missed the exam — they are excluded from mean score.</div>
          <div id="autosave-status" class="small text-muted mt-1"></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          @if($canEdit)
            <button type="submit" name="submit_for_review" value="0" class="btn btn-outline-secondary" @disabled($students->isEmpty())>
              <i class="bi bi-save2 me-1"></i>Save draft
            </button>
            @if(!in_array($exam->status, ['moderation'], true))
              <button type="submit" name="submit_for_review" value="1" class="btn btn-settings-primary" @disabled($students->isEmpty()) onclick="return confirm('Submit all marks for review? Teachers will not be able to edit this exam after submission.');">
                <i class="bi bi-send-check me-1"></i>Submit for review
              </button>
            @endif
          @endif
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const canEdit = @json($canEdit);
  const draftUrl = @json(route('academics.exam-marks.bulk.draft'));
  const csrf = @json(csrf_token());
  const examId = @json($exam->id);
  const classroomId = @json($class->id);
  const statusEl = document.getElementById('autosave-status');
  let timer = null;
  let saving = false;

  document.querySelectorAll('.score-input').forEach(inp => {
    inp.addEventListener('change', () => {
      const v = inp.value.trim();
      if (v === '') return;
      let n = parseFloat(v);
      if (isNaN(n)) { inp.value = ''; return; }
      if (n < 0) n = 0;
      if (n > 100) n = 100;
      inp.value = n.toFixed(2);
      const row = inp.closest('tr');
      const absent = row?.querySelector('.absent-toggle');
      if (absent) absent.checked = false;
    });
  });

  document.querySelectorAll('.absent-toggle').forEach(box => {
    box.addEventListener('change', () => {
      const row = box.closest('tr');
      const score = row?.querySelector('.score-input');
      if (!score) return;
      if (box.checked) {
        score.value = '';
        score.disabled = true;
      } else if (canEdit) {
        score.disabled = false;
      }
      scheduleAutosave();
    });
  });

  function collectRows() {
    const rows = [];
    document.querySelectorAll('input[data-student-id]').forEach((hidden, i) => {
      const score = document.querySelector(`input[name="rows[${i}][score]"]`);
      const remark = document.querySelector(`input[name="rows[${i}][subject_remark]"]`);
      const absent = document.querySelector(`input[name="rows[${i}][is_absent]"][type="checkbox"]`);
      rows.push({
        student_id: parseInt(hidden.value, 10),
        score: absent?.checked ? null : (score?.value?.trim() || null),
        subject_remark: remark?.value?.trim() || null,
        is_absent: absent?.checked ? 1 : 0,
      });
    });
    return rows;
  }

  async function autosave() {
    if (!canEdit || saving) return;
    saving = true;
    statusEl.textContent = 'Saving draft…';
    try {
      const res = await fetch(draftUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ exam_id: examId, classroom_id: classroomId, rows: collectRows() }),
      });
      const data = await res.json();
      if (data.success) {
        statusEl.textContent = `Draft saved (${data.saved} updated) · ${new Date().toLocaleTimeString()}`;
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
    if (!canEdit) return;
    clearTimeout(timer);
    timer = setTimeout(autosave, 1200);
  }

  document.querySelectorAll('.mark-field').forEach(el => {
    el.addEventListener('input', scheduleAutosave);
    el.addEventListener('change', scheduleAutosave);
  });
})();
</script>
@endpush
@endsection

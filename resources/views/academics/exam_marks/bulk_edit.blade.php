@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('academics.exam_reports.partials.cbc_grade_styles')
@endpush

@section('content')
@php use App\Support\CbcGradePresentation; @endphp
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Enter Marks</h1>
        <p class="text-muted mb-1">
          Results: <strong>{{ $exam->name }}</strong> → <strong>{{ $subject->name }}</strong> → <strong>{{ $class->name }}</strong>
          @if(!empty($streamLabel))
            · <span class="mark-sheet-stream-pill">{{ $streamLabel }}</span>
          @endif
        </p>
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
        <div class="exam-entry-list" id="exam-entry-list">
          @foreach($students as $i => $s)
            @php
              $m = $existing[$s->id] ?? null;
              $score = $m?->is_absent ? null : $m?->score_raw;
              $remark = $m?->subject_remark;
              $isAbsent = (bool) old("rows.$i.is_absent", $m?->is_absent ?? false);
              $initials = collect(preg_split('/\s+/', trim($s->full_name ?? '')) ?: [])
                ->filter()
                ->take(2)
                ->map(fn ($p) => strtoupper(substr($p, 0, 1)))
                ->implode('');
              $avatarClass = ['', ' student-avatar--alt', ' student-avatar--alt2'][$i % 3];
              $grade = CbcGradePresentation::forRawScore(
                is_numeric($score) ? (float) $score : null,
                $maxMarks ?? 100,
                $classroomId ?? null
              );
            @endphp
            <div class="exam-entry-row" data-row-index="{{ $i }}">
              <div class="exam-entry-student">
                <span class="student-avatar{{ $avatarClass }}">{{ $initials ?: '?' }}</span>
                <div class="min-width-0">
                  <div class="fw-semibold">{{ $s->full_name }}</div>
                  <div class="student-meta">
                    {{ $s->admission_number ?? '—' }}
                    @if($s->stream?->name && empty($streamLabel))
                      · <span class="mark-sheet-stream-pill">{{ $s->stream->name }}</span>
                    @endif
                  </div>
                  <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $s->id }}" data-student-id="{{ $s->id }}">
                </div>
              </div>
              <div class="exam-entry-score">
                <label class="visually-hidden" for="score-{{ $i }}">Score</label>
                <input type="number" step="0.01" min="0" id="score-{{ $i }}"
                       class="form-control text-center score-input mark-field"
                       name="rows[{{ $i }}][score]"
                       value="{{ old("rows.$i.score", $score) }}"
                       placeholder="—"
                       data-max="{{ $maxMarks ?? 100 }}"
                       @disabled(!$canEdit || $isAbsent)>
                <span class="text-muted small">out of</span>
                <span class="max-marks">{{ (float) ($maxMarks ?? 100) }}</span>
                <label class="visually-hidden" for="absent-{{ $i }}">Absent</label>
                <div class="form-check ms-1">
                  <input type="checkbox" class="form-check-input absent-toggle mark-field" id="absent-{{ $i }}"
                         name="rows[{{ $i }}][is_absent]" value="1" @checked($isAbsent) @disabled(!$canEdit)>
                  <label class="form-check-label small" for="absent-{{ $i }}">Absent</label>
                </div>
              </div>
              <div>
                <input type="text" class="form-control mark-field" name="rows[{{ $i }}][subject_remark]"
                       value="{{ old("rows.$i.subject_remark", $remark) }}" maxlength="500"
                       placeholder="Teacher remarks" @disabled(!$canEdit)>
              </div>
              <div class="exam-entry-grade">
                <span class="grade-badge-slot" data-grade-slot>
                  @if($grade)
                    @include('academics.exam_reports.partials.cbc_grade_badge', ['grade' => $grade, 'wide' => true])
                  @endif
                </span>
              </div>
            </div>
          @endforeach
          @if($students->isEmpty())
            <div class="p-4 text-center text-muted">No students in this class.</div>
          @endif
        </div>

        @if($students->isNotEmpty())
          <div class="class-mean-bar">
            <span class="fw-semibold">Class mean score:</span>
            <span id="class-mean-value" class="fw-bold">—</span>
            <span id="class-mean-grade"></span>
          </div>
        @endif
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
  const maxMarks = @json((float) ($maxMarks ?? 100));
  const gradeBands = @json(CbcGradePresentation::standardBands());
  const statusEl = document.getElementById('autosave-status');
  const meanEl = document.getElementById('class-mean-value');
  const meanGradeEl = document.getElementById('class-mean-grade');
  let timer = null;
  let saving = false;

  function gradeForPercent(percent) {
    if (percent === null || isNaN(percent)) return null;
    const p = Math.max(0, Math.min(100, percent));
    for (const band of gradeBands) {
      if (p >= band.min && p <= band.max) {
        return { ...band, percent: p };
      }
    }
    return null;
  }

  function gradeForScore(score) {
    if (score === null || score === '' || isNaN(parseFloat(score))) return null;
    const pct = (parseFloat(score) / maxMarks) * 100;
    return gradeForPercent(pct);
  }

  function renderBadge(grade, wide) {
    if (!grade) return '';
    const cls = `cbc-grade-badge cbc-grade--${grade.tier}${wide ? ' cbc-grade-badge--wide' : ''}`;
    const text = wide ? grade.label : grade.short;
    return `<span class="${cls}" title="${grade.label} (${grade.percent.toFixed(1)}%)">${text}</span>`;
  }

  function updateRowGrade(row) {
    const score = row.querySelector('.score-input');
    const absent = row.querySelector('.absent-toggle');
    const slot = row.querySelector('[data-grade-slot]');
    if (!slot) return;
    if (absent?.checked) {
      slot.innerHTML = '';
      return;
    }
    const grade = gradeForScore(score?.value);
    slot.innerHTML = grade ? renderBadge(grade, true) : '';
  }

  function updateClassMean() {
    if (!meanEl) return;
    const scores = [];
    document.querySelectorAll('.exam-entry-row').forEach(row => {
      const absent = row.querySelector('.absent-toggle');
      const score = row.querySelector('.score-input');
      if (absent?.checked) return;
      const v = score?.value?.trim();
      if (v === '' || isNaN(parseFloat(v))) return;
      scores.push(parseFloat(v));
    });
    if (!scores.length) {
      meanEl.textContent = '—';
      if (meanGradeEl) meanGradeEl.innerHTML = '';
      return;
    }
    const meanPct = (scores.reduce((a, b) => a + b, 0) / scores.length / maxMarks) * 100;
    const meanRaw = scores.reduce((a, b) => a + b, 0) / scores.length;
    meanEl.textContent = meanRaw.toFixed(2);
    const grade = gradeForPercent(meanPct);
    if (meanGradeEl) {
      meanGradeEl.innerHTML = grade ? renderBadge(grade, true) : '';
    }
  }

  function refreshGrades() {
    document.querySelectorAll('.exam-entry-row').forEach(updateRowGrade);
    updateClassMean();
  }

  document.querySelectorAll('.score-input').forEach(inp => {
    inp.addEventListener('change', () => {
      const v = inp.value.trim();
      if (v === '') {
        refreshGrades();
        return;
      }
      let n = parseFloat(v);
      if (isNaN(n)) { inp.value = ''; refreshGrades(); return; }
      if (n < 0) n = 0;
      if (n > maxMarks) n = maxMarks;
      inp.value = n.toFixed(2);
      const row = inp.closest('.exam-entry-row');
      const absent = row?.querySelector('.absent-toggle');
      if (absent) absent.checked = false;
      refreshGrades();
    });
    inp.addEventListener('input', refreshGrades);
  });

  document.querySelectorAll('.absent-toggle').forEach(box => {
    box.addEventListener('change', () => {
      const row = box.closest('.exam-entry-row');
      const score = row?.querySelector('.score-input');
      if (!score) return;
      if (box.checked) {
        score.value = '';
        score.disabled = true;
      } else if (canEdit) {
        score.disabled = false;
      }
      refreshGrades();
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

  refreshGrades();
})();
</script>
@endpush
@endsection

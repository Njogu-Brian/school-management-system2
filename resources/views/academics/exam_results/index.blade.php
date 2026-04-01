@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
      .exam-results-filters { flex: 1 1 auto; min-width: 0; }
      .exam-results-filters .filter-field { min-width: 10rem; max-width: 100%; }
      .exam-results-filters .filter-field--wide { min-width: 14rem; }
      @media (min-width: 992px) {
        .exam-results-filters .filter-field--paper { min-width: 17rem; }
      }
    </style>
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex flex-column flex-xl-row flex-xl-wrap align-items-stretch align-items-xl-end justify-content-xl-between gap-3">
      <div class="flex-shrink-0">
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Exam Results</h1>
        <p class="text-muted mb-0">Review and publish results to report cards.</p>
      </div>
      <form method="get" class="exam-results-filters d-flex flex-wrap align-items-end gap-3">
        <div class="filter-field filter-field--wide d-flex flex-column">
          <label for="erf_exam_type" class="form-label small text-muted mb-1">Exam type</label>
          <select id="erf_exam_type" name="exam_type_id" class="form-select" onchange="this.form.submit()">
            <option value="">All types</option>
            @foreach($examTypes ?? [] as $et)
              <option value="{{ $et->id }}" @selected((string)($examTypeId ?? '') === (string)$et->id)>{{ $et->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="filter-field d-flex flex-column flex-grow-1" style="min-width: 12rem;">
          <label for="erf_session" class="form-label small text-muted mb-1">Sitting</label>
          <select id="erf_session" name="exam_session_id" class="form-select" onchange="this.form.submit()">
            <option value="">All sittings</option>
            @foreach($sessions ?? [] as $s)
              <option value="{{ $s->id }}" @selected((string)($examSessionId ?? '') === (string)$s->id)>
                {{ $s->examType->name ?? '' }} — {{ $s->classroom->name ?? '' }} ({{ $s->academicYear->year ?? '' }} {{ $s->term->name ?? '' }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="filter-field filter-field--paper d-flex flex-column flex-grow-1" style="min-width: 14rem;">
          <label for="erf_paper" class="form-label small text-muted mb-1">Paper / subject</label>
          <select id="erf_paper" name="exam_id" class="form-select" onchange="this.form.submit()">
            <option value="">All papers in scope</option>
            @foreach($papers ?? [] as $e)
              <option value="{{ $e->id }}" @selected((string)($examId ?? '') === (string)$e->id)>
                {{ $e->subject?->name ?? 'Subject' }} — {{ $e->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="d-flex align-items-end">
          <button type="submit" class="btn btn-ghost-strong" title="Apply filters"><i class="bi bi-search"></i></button>
        </div>
      </form>
    </div>

    @includeIf('partials.alerts')

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Exam</th>
                <th class="text-center">Mark</th>
                <th class="text-center">Grade</th>
                <th class="text-center">Points</th>
                <th>Remark</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($marks as $m)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $m->student?->full_name ?? '-' }}</td>
                  <td>{{ $m->subject?->name ?? '-' }}</td>
                  <td>{{ $m->exam?->name ?? '-' }}</td>
                  <td class="text-center">{{ $m->score_raw ?? '-' }}</td>
                  <td class="text-center"><span class="pill-badge pill-success">{{ $m->grade_label ?? '-' }}</span></td>
                  <td class="text-center"><span class="pill-badge pill-info">{{ $m->pl_level ?? '-' }}</span></td>
                  <td>{{ $m->subject_remark ?? '-' }}</td>
                  <td>
                    @if($m->status=='submitted')
                      <span class="pill-badge pill-muted">Submitted</span>
                    @elseif($m->status=='approved')
                      <span class="pill-badge pill-success">Approved</span>
                    @else
                      <span class="pill-badge pill-muted">{{ ucfirst($m->status) }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No results available.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if(method_exists($marks,'links'))
        <div class="card-footer">{{ $marks->withQueryString()->links() }}</div>
      @endif
    </div>

    @if($examId)
      <div class="mt-3 text-end">
        <form action="{{ route('academics.exams.publish', $examId) }}" method="post" onsubmit="return confirm('Publish results for this exam to report cards?');">
          @csrf
          <button class="btn btn-settings-primary"><i class="bi bi-cloud-upload"></i> Publish Results</button>
        </form>
      </div>
    @endif
  </div>
</div>
@endsection

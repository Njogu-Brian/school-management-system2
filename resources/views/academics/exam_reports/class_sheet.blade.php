@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exam Reports &amp; Analysis</div>
        <h1 class="mb-1">Class Mark Sheet</h1>
        <p class="text-muted mb-0">Generate per-class sheets with subject scores, totals, averages, and positions.</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.teacher-performance') }}">Teacher Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.subject-performance') }}">Subject Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-2">
            <label class="form-label">Mode</label>
            <select name="mode" class="form-select">
              <option value="exam" {{ $mode === 'exam' ? 'selected' : '' }}>Per Exam</option>
              <option value="term" {{ $mode === 'term' ? 'selected' : '' }}>Termly</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" {{ $mode === 'term' ? 'disabled' : '' }}>
              <option value="">Select Exam</option>
              @foreach($exams as $exam)
                <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                  {{ $exam->name }} - {{ $exam->academicYear->year ?? '' }} {{ $exam->term ? 'Term ' . $exam->term->name : '' }}
                </option>
              @endforeach
            </select>
            @if($mode === 'term')
              <input type="hidden" name="exam_id" value="">
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" {{ $mode === 'exam' ? 'disabled' : '' }}>
              <option value="">Select Year</option>
              @foreach($academicYears as $y)
                <option value="{{ $y->id }}" {{ request('academic_year_id') == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
              @endforeach
            </select>
            @if($mode === 'exam')
              <input type="hidden" name="academic_year_id" value="">
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" {{ $mode === 'exam' ? 'disabled' : '' }}>
              <option value="">Select Term</option>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ request('term_id') == $t->id ? 'selected' : '' }}>
                  {{ $t->academicYear->year ?? '' }} · {{ $t->name }}
                </option>
              @endforeach
            </select>
            @if($mode === 'exam')
              <input type="hidden" name="term_id" value="">
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select" required>
              <option value="">Select Class</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Stream (Optional)</label>
            <select name="stream_id" class="form-select">
              <option value="">All Streams</option>
              @foreach($streams as $stream)
                <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-settings-primary">Generate</button>
            @if($payload)
              <a class="btn btn-outline-secondary"
                 href="{{ route('academics.exam-reports.export.class-sheet', request()->query()) }}">
                Export XLSX
              </a>
            @endif
            @if($mode === 'term' && request('academic_year_id') && request('term_id'))
              <a class="btn btn-outline-secondary"
                 href="{{ route('academics.exam-reports.export.term-workbook', ['academic_year_id' => request('academic_year_id'), 'term_id' => request('term_id')]) }}">
                @if(!empty($examReportsFullAccess))
                  Export Term Workbook (All Classes)
                @else
                  Export Term Workbook (My Classes)
                @endif
              </a>
            @endif
          </div>
        </form>
      </div>
    </div>

    @if($classrooms->isEmpty())
      <div class="alert alert-warning border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-info-circle me-1"></i>
        No classes are available for your account. Ask an administrator to assign you to a class or assign your senior-teacher campus.
      </div>
    @endif

    @if($payload)
      @php
        $subjects = $payload['subjects'] ?? [];
        $rows = $payload['rows'] ?? [];
      @endphp

      <div class="settings-card">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-table"></i>
          <h5 class="mb-0">Mark Sheet</h5>
          <span class="text-muted small ms-2">
            {{ ($payload['meta']['classroom']['name'] ?? '') }}
            @if(!empty($payload['meta']['mode']) && $payload['meta']['mode'] === 'term')
              · Termly
            @else
              · {{ $payload['meta']['exam']['name'] ?? '' }}
            @endif
          </span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="min-width:60px">#</th>
                  <th style="min-width:130px">Adm No</th>
                  <th style="min-width:220px">Student</th>
                  @foreach($subjects as $s)
                    <th style="min-width:120px">{{ $s['code'] ? $s['code'] : $s['name'] }}</th>
                    <th style="min-width:80px">Pos</th>
                  @endforeach
                  <th style="min-width:110px">Total</th>
                  <th style="min-width:110px">Average</th>
                  <th style="min-width:110px">Class Pos</th>
                  <th style="min-width:110px">Stream Pos</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $i => $r)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r['admission_number'] ?? '' }}</td>
                    <td class="fw-semibold">{{ $r['name'] ?? '' }}</td>
                    @foreach($subjects as $s)
                      @php $sid = $s['id']; @endphp
                      <td>{{ data_get($r, "subject_scores.$sid") }}</td>
                      <td class="text-muted">{{ data_get($r, "subject_positions.$sid") }}</td>
                    @endforeach
                    <td class="fw-semibold">{{ $r['total'] }}</td>
                    <td>{{ $r['average'] }}</td>
                    <td class="fw-semibold">{{ $r['class_position'] ?? $r['position'] }}</td>
                    <td class="text-muted">{{ $r['stream_position'] }}</td>
                  </tr>
                @empty
                  <tr><td colspan="{{ 7 + (count($subjects) * 2) }}" class="text-center text-muted py-4">No data found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection


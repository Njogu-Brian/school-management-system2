@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('academics.exam_reports.partials.exam_report_print_css')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exam Reports &amp; Analysis</div>
        <h1 class="mb-1">Teacher Performance</h1>
        <p class="text-muted mb-0">Rank teachers by subjects taught (per class or school-wide).</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.class-sheet') }}">Class Sheet</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.subject-performance') }}">Subject Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-2">
            <label class="form-label">Scope</label>
            <select name="scope" class="form-select">
              <option value="class" {{ ($scope ?? 'class') === 'class' ? 'selected' : '' }}>Class</option>
              @if(!empty($examReportsFullAccess))
                <option value="school" {{ ($scope ?? 'class') === 'school' ? 'selected' : '' }}>School-wide</option>
              @endif
            </select>
            @if(empty($examReportsFullAccess))
              <small class="text-muted d-block mt-1">School-wide ranking is available to administrators.</small>
            @endif
          </div>

          <div class="col-md-4">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" required>
              <option value="">Select Exam</option>
              @foreach($exams as $exam)
                <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                  {{ $exam->name }} - {{ $exam->academicYear->year ?? '' }} {{ $exam->term ? 'Term ' . $exam->term->name : '' }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select" {{ ($scope ?? 'class') === 'school' ? 'disabled' : 'required' }}>
              <option value="">Select Class</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
            @if(($scope ?? 'class') === 'school')
              <input type="hidden" name="classroom_id" value="">
            @endif
          </div>

          <div class="col-md-3">
            <label class="form-label">Stream (Optional)</label>
            <select name="stream_id" class="form-select" {{ ($scope ?? 'class') === 'school' ? 'disabled' : '' }}>
              <option value="">All Streams</option>
              @foreach($streams as $stream)
                <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
              @endforeach
            </select>
            @if(($scope ?? 'class') === 'school')
              <input type="hidden" name="stream_id" value="">
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Subject (Optional)</label>
            <input name="subject_id" class="form-control" value="{{ request('subject_id') }}" placeholder="Subject ID">
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-settings-primary">Generate</button>
          </div>
        </form>
      </div>
    </div>

    @if($classrooms->isEmpty() && (($scope ?? 'class') === 'class'))
      <div class="alert alert-warning border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-info-circle me-1"></i>
        No classes are available for your account. Ask an administrator to assign you to a class or assign your senior-teacher campus.
      </div>
    @endif

    @if($payload)
      <div class="d-flex justify-content-end mb-2 no-print gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      </div>
      <div id="exam-report-print-area" class="exam-report-print-root">
        @include('academics.exam_reports.partials.report_letterhead', [
          'reportTitle' => 'Teacher Performance',
          'reportSubtitle' => 'Exam analytics — teacher rankings',
          'generatedAt' => now(),
          'generatedBy' => auth()->user()?->name,
        ])
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="settings-card h-100">
            <div class="card-header d-flex align-items-center gap-2 d-print-none"><i class="bi bi-people"></i><h5 class="mb-0">Teacher Rankings</h5></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0 exam-report-marks-table">
                  <thead class="table-light"><tr><th>Rank</th><th>Teacher</th><th>Subjects</th><th>Mean</th></tr></thead>
                  <tbody>
                    @foreach(($payload['per_teacher'] ?? []) as $row)
                      <tr>
                        <td class="fw-semibold">{{ $row['rank'] }}</td>
                        <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                        <td>{{ $row['subjects_count'] }}</td>
                        <td>{{ $row['mean_of_subject_means'] }}</td>
                      </tr>
                    @endforeach
                    @if(empty($payload['per_teacher']))
                      <tr><td colspan="4" class="text-center text-muted py-4">No teacher stats found.</td></tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="settings-card h-100">
            <div class="card-header d-flex align-items-center gap-2 d-print-none"><i class="bi bi-bar-chart"></i><h5 class="mb-0">Subject-by-Subject</h5></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0 exam-report-marks-table">
                  @php $isSchool = (($scope ?? 'class') === 'school'); @endphp
                  <thead class="table-light">
                    <tr>
                      <th>Subject</th>
                      <th>Teacher</th>
                      @if($isSchool)<th>Rank</th>@endif
                      <th>Mean</th>
                      <th>Pass %</th>
                      <th>Count</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach(($isSchool ? ($payload['per_subject_teacher'] ?? []) : ($payload['per_subject'] ?? [])) as $row)
                      <tr>
                        <td class="fw-semibold">{{ $row['subject'] }}</td>
                        <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                        @if($isSchool)<td class="fw-semibold">{{ $row['rank_in_subject'] }}</td>@endif
                        <td>{{ $row['mean'] }}</td>
                        <td>{{ $row['pass_rate'] }}</td>
                        <td>{{ $row['count'] }}</td>
                      </tr>
                    @endforeach
                    @if($isSchool ? empty($payload['per_subject_teacher']) : empty($payload['per_subject']))
                      <tr><td colspan="{{ $isSchool ? 6 : 5 }}" class="text-center text-muted py-4">No subject stats found.</td></tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    @endif
  </div>
</div>
@endsection


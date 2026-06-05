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
        <h1 class="mb-1">Subject Performance</h1>
        <p class="text-muted mb-0">Class-by-class subject rankings with averages for one exam or a full term.</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.class-sheet') }}">Class Sheet</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.teacher-performance') }}">Teacher Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    @include('academics.exam_reports.partials.analysis_filters')

    @if(($classrooms ?? collect())->isEmpty())
      <div class="alert alert-warning border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-info-circle me-1"></i>
        No classes are available for your account. Ask an administrator to assign you to a class or assign your senior-teacher campus.
      </div>
    @endif

    @if(!empty($notice))
      <div class="alert alert-warning border-0 shadow-sm mb-3" role="alert">{{ $notice }}</div>
    @endif

    @if($payload)
      <div class="d-flex justify-content-end mb-2 no-print gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      </div>
      <div id="exam-report-print-area" class="exam-report-print-root">
        @include('academics.exam_reports.partials.report_letterhead', [
          'reportTitle' => 'Subject Performance',
          'reportSubtitle' => ($analysisFlow ?? '') === 'term' ? 'Termly subject rankings' : 'Exam subject rankings',
          'generatedAt' => now(),
          'generatedBy' => auth()->user()?->name,
        ])
      <div class="settings-card">
        <div class="card-header d-flex align-items-center gap-2 d-print-none"><i class="bi bi-graph-up"></i><h5 class="mb-0">Subject Rankings</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0 exam-report-marks-table">
              <thead class="table-light"><tr><th>Rank</th><th>Subject</th><th>Mean</th><th>Pass %</th><th>Count</th><th>Max</th><th>Min</th></tr></thead>
              <tbody>
                @foreach(($payload['subjects'] ?? []) as $i => $row)
                  <tr>
                    <td class="fw-semibold">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $row['subject'] }}</td>
                    <td>{{ $row['mean'] }}</td>
                    <td>{{ $row['pass_rate'] }}</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ $row['max'] }}</td>
                    <td>{{ $row['min'] }}</td>
                  </tr>
                @endforeach
                @if(empty($payload['subjects']))
                  <tr><td colspan="7" class="text-center text-muted py-4">No subject stats found.</td></tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>
      </div>
    @endif
  </div>
</div>
@endsection

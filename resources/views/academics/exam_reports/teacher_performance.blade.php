@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('academics.exam_reports.partials.exam_report_print_css')
@endpush

@section('content')
@php
  $reportView = $reportView ?? 'class';
  $prevTerm = $payload['meta']['previous_term'] ?? null;
  $hasImprovement = ! empty($payload['meta']['has_improvement']);
@endphp
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exam Reports &amp; Analysis</div>
        <h1 class="mb-1">Teacher Performance</h1>
        <p class="text-muted mb-0">
          Class performance, school-wide rankings by section, or one teacher in detail — with improvement versus the previous term.
        </p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.class-sheet') }}">Class Sheet</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.subject-performance') }}">Subject Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    @include('academics.exam_reports.partials.analysis_filters', [
      'showTeacherViews' => true,
      'showSchoolScope' => false,
    ])

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
        @php $exportQ = request()->query(); $exportQ['load'] = 1; $exportQ['view'] = $reportView; @endphp
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('academics.exam-reports.export.teacher-performance', $exportQ) }}">
          <i class="bi bi-file-earmark-spreadsheet"></i> Export XLSX
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      </div>
      <div id="exam-report-print-area" class="exam-report-print-root">
        @include('academics.exam_reports.partials.report_letterhead', [
          'reportTitle' => 'Teacher Performance',
          'reportSubtitle' => match ($reportView) {
            'school' => 'All teachers by school phase',
            'teacher' => 'Individual teacher detail',
            default => 'Class teacher rankings',
          },
          'generatedAt' => now(),
          'generatedBy' => auth()->user()?->name,
        ])

        @if($prevTerm)
          <div class="alert alert-light border mb-3">
            Improvement vs previous term:
            <strong>{{ $prevTerm['academic_year'] ?? '' }} · {{ $prevTerm['term_name'] ?? '' }}</strong>
            @if(! $hasImprovement)
              <span class="text-muted">(no comparable sitting found)</span>
            @endif
          </div>
        @endif

        @if($reportView === 'school')
          @foreach(collect($payload['by_category'] ?? []) as $category)
            <div class="settings-card mb-3">
              <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <i class="bi bi-layers"></i>
                <h5 class="mb-0">{{ $category['label'] ?? 'Category' }}</h5>
                @if(!empty($category['detail']))
                  <span class="text-muted small">{{ $category['detail'] }}</span>
                @endif
              </div>
              <div class="card-body">
                @if(!empty($category['most_improved']))
                  <p class="small text-muted mb-2">
                    Top improved:
                    {{ collect($category['most_improved'])->take(3)->map(fn ($r) => ($r['teacher'] ?? 'Teacher').' ('.($r['improvement'] >= 0 ? '+' : '').$r['improvement'].')')->implode(', ') }}
                  </p>
                @endif
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-people"></i><strong>Teacher Rankings</strong></div>
                    <div class="table-responsive">
                      <table class="table table-modern align-middle mb-0 exam-report-marks-table">
                        <thead class="table-light">
                          <tr>
                            <th>Rank</th><th>Teacher</th><th>Subjects</th><th>Mean</th><th>Prev</th><th>Δ</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse(($category['per_teacher'] ?? []) as $row)
                            <tr>
                              <td class="fw-semibold">{{ $row['rank'] }}</td>
                              <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                              <td>{{ $row['subjects_count'] }}</td>
                              <td>{{ $row['mean_of_subject_means'] }}</td>
                              <td>{{ $row['prev_mean'] ?? '—' }}</td>
                              <td class="fw-semibold">{{ isset($row['improvement']) ? (($row['improvement'] > 0 ? '+' : '').$row['improvement']) : '—' }}</td>
                            </tr>
                          @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No teacher stats in this phase.</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-bar-chart"></i><strong>Subject-by-Subject</strong></div>
                    <div class="table-responsive">
                      <table class="table table-modern align-middle mb-0 exam-report-marks-table">
                        <thead class="table-light">
                          <tr>
                            <th>Subject</th><th>Teacher</th><th>Rank</th><th>Mean</th><th>Prev</th><th>Δ</th><th>Pass %</th><th>Count</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse(($category['per_subject_teacher'] ?? []) as $row)
                            <tr>
                              <td class="fw-semibold">{{ $row['subject'] }}</td>
                              <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                              <td class="fw-semibold">{{ $row['rank_in_subject'] }}</td>
                              <td>{{ $row['mean'] }}</td>
                              <td>{{ $row['prev_mean'] ?? '—' }}</td>
                              <td class="fw-semibold">{{ isset($row['improvement']) ? (($row['improvement'] > 0 ? '+' : '').$row['improvement']) : '—' }}</td>
                              <td>{{ $row['pass_rate'] }}</td>
                              <td>{{ $row['count'] }}</td>
                            </tr>
                          @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">No subject stats in this phase.</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endforeach

        @elseif($reportView === 'teacher')
          @php $summary = $payload['summary'] ?? []; @endphp
          <div class="settings-card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
              <i class="bi bi-person-badge"></i>
              <h5 class="mb-0">{{ $payload['teacher'] ?? 'Teacher' }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="text-muted small">Subjects</div><div class="fs-5 fw-semibold">{{ $summary['subjects_count'] ?? 0 }}</div></div>
                <div class="col-md-3"><div class="text-muted small">Classes</div><div class="fs-5 fw-semibold">{{ $summary['classes_count'] ?? 0 }}</div></div>
                <div class="col-md-3"><div class="text-muted small">Overall mean</div><div class="fs-5 fw-semibold">{{ $summary['mean_of_subject_means'] ?? '—' }}</div></div>
                <div class="col-md-3">
                  <div class="text-muted small">Improvement</div>
                  <div class="fs-5 fw-semibold">
                    @if(isset($summary['improvement']))
                      {{ ($summary['improvement'] > 0 ? '+' : '').$summary['improvement'] }}
                      <span class="text-muted small">(prev {{ $summary['prev_mean'] ?? '—' }})</span>
                    @else
                      —
                    @endif
                  </div>
                </div>
              </div>
              @if(!empty($summary['sections']))
                <p class="small text-muted">Sections: {{ implode(' · ', $summary['sections']) }}</p>
              @endif
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0 exam-report-marks-table">
                  <thead class="table-light">
                    <tr>
                      <th>Section</th><th>Class</th><th>Subject</th><th>Mean</th><th>Prev</th><th>Δ</th><th>Pass %</th><th>Count</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse(($payload['rows'] ?? []) as $row)
                      <tr>
                        <td>{{ $row['category'] ?? '—' }}</td>
                        <td class="fw-semibold">{{ $row['classroom'] ?? '—' }}</td>
                        <td>{{ $row['subject'] }}</td>
                        <td>{{ $row['mean'] }}</td>
                        <td>{{ $row['prev_mean'] ?? '—' }}</td>
                        <td class="fw-semibold">{{ isset($row['improvement']) ? (($row['improvement'] > 0 ? '+' : '').$row['improvement']) : '—' }}</td>
                        <td>{{ $row['pass_rate'] }}</td>
                        <td>{{ $row['count'] }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="8" class="text-center text-muted py-4">No subject rows for this teacher.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        @else
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="settings-card h-100">
                <div class="card-header d-flex align-items-center gap-2 d-print-none"><i class="bi bi-people"></i><h5 class="mb-0">Teacher Rankings</h5></div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0 exam-report-marks-table">
                      <thead class="table-light">
                        <tr><th>Rank</th><th>Teacher</th><th>Subjects</th><th>Mean</th><th>Prev</th><th>Δ</th></tr>
                      </thead>
                      <tbody>
                        @foreach(($payload['per_teacher'] ?? []) as $row)
                          <tr>
                            <td class="fw-semibold">{{ $row['rank'] }}</td>
                            <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                            <td>{{ $row['subjects_count'] }}</td>
                            <td>{{ $row['mean_of_subject_means'] }}</td>
                            <td>{{ $row['prev_mean'] ?? '—' }}</td>
                            <td class="fw-semibold">{{ isset($row['improvement']) ? (($row['improvement'] > 0 ? '+' : '').$row['improvement']) : '—' }}</td>
                          </tr>
                        @endforeach
                        @if(empty($payload['per_teacher']))
                          <tr><td colspan="6" class="text-center text-muted py-4">No teacher stats found.</td></tr>
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
                      <thead class="table-light">
                        <tr><th>Subject</th><th>Teacher</th><th>Mean</th><th>Prev</th><th>Δ</th><th>Pass %</th><th>Count</th></tr>
                      </thead>
                      <tbody>
                        @foreach(($payload['per_subject'] ?? []) as $row)
                          <tr>
                            <td class="fw-semibold">{{ $row['subject'] }}</td>
                            <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                            <td>{{ $row['mean'] }}</td>
                            <td>{{ $row['prev_mean'] ?? '—' }}</td>
                            <td class="fw-semibold">{{ isset($row['improvement']) ? (($row['improvement'] > 0 ? '+' : '').$row['improvement']) : '—' }}</td>
                            <td>{{ $row['pass_rate'] }}</td>
                            <td>{{ $row['count'] }}</td>
                          </tr>
                        @endforeach
                        @if(empty($payload['per_subject']))
                          <tr><td colspan="7" class="text-center text-muted py-4">No subject stats found.</td></tr>
                        @endif
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          @if(! empty($payload['most_improved_teachers']))
            <div class="settings-card mt-3">
              <div class="card-header"><h5 class="mb-0">Most improved teachers</h5></div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-modern mb-0">
                    <thead class="table-light"><tr><th>Teacher</th><th>Prev</th><th>Current</th><th>Δ</th></tr></thead>
                    <tbody>
                      @foreach($payload['most_improved_teachers'] as $row)
                        <tr>
                          <td>{{ $row['teacher'] ?? 'Unassigned' }}</td>
                          <td>{{ $row['prev_mean'] ?? '—' }}</td>
                          <td>{{ $row['mean_of_subject_means'] ?? '—' }}</td>
                          <td class="fw-semibold">{{ ($row['improvement'] > 0 ? '+' : '').$row['improvement'] }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @endif

          @if(($analysisFlow ?? '') === 'term' && ! empty($payload['most_improved']))
            @php
              $cid = request('classroom_id');
              $showStreamCol = ! empty($streamsByClassroom[$cid] ?? $streamsByClassroom[(string) $cid] ?? []);
            @endphp
            <div class="col-12 mt-3">
              <div class="settings-card">
                <div class="card-body p-0">
                  @include('academics.exam_reports.partials.most_improved_panel', [
                    'payload' => $payload,
                    'showStreamColumn' => $showStreamCol,
                    'allowSittingPicker' => true,
                  ])
                </div>
              </div>
            </div>
          @endif
        @endif
      </div>
    @endif
  </div>
</div>
@endsection

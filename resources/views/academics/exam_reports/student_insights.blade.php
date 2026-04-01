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
        <h1 class="mb-1">Student Insights</h1>
        <p class="text-muted mb-0">Top students and most improved (exam-to-exam or term-to-term).</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.class-sheet') }}">Class Sheet</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.teacher-performance') }}">Teacher Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.subject-performance') }}">Subject Performance</a>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-2">
            <label class="form-label">Mode</label>
            <select name="mode" class="form-select">
              <option value="exam" {{ ($mode ?? 'exam') === 'exam' ? 'selected' : '' }}>Per Exam</option>
              <option value="term" {{ ($mode ?? 'exam') === 'term' ? 'selected' : '' }}>Termly</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" {{ ($mode ?? 'exam') === 'term' ? 'disabled' : '' }}>
              <option value="">Select Exam</option>
              @foreach($exams as $exam)
                <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                  {{ $exam->name }} - {{ $exam->academicYear->year ?? '' }} {{ $exam->term ? 'Term ' . $exam->term->name : '' }}
                </option>
              @endforeach
            </select>
            @if(($mode ?? 'exam') === 'term')
              <input type="hidden" name="exam_id" value="">
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" {{ ($mode ?? 'exam') === 'exam' ? 'disabled' : '' }}>
              <option value="">Select Year</option>
              @foreach($academicYears as $y)
                <option value="{{ $y->id }}" {{ request('academic_year_id') == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
              @endforeach
            </select>
            @if(($mode ?? 'exam') === 'exam')
              <input type="hidden" name="academic_year_id" value="">
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" {{ ($mode ?? 'exam') === 'exam' ? 'disabled' : '' }}>
              <option value="">Select Term</option>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ request('term_id') == $t->id ? 'selected' : '' }}>
                  {{ $t->academicYear->year ?? '' }} · {{ $t->name }}
                </option>
              @endforeach
            </select>
            @if(($mode ?? 'exam') === 'exam')
              <input type="hidden" name="term_id" value="">
            @endif
          </div>

          <div class="col-md-4">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select" required>
              <option value="">Select Class</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
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
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="settings-card h-100">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-award"></i><h5 class="mb-0">Top Students</h5></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                  <thead class="table-light"><tr><th>Pos</th><th>Adm No</th><th>Student</th><th>Total</th><th>Avg</th></tr></thead>
                  <tbody>
                    @foreach(($payload['top_students'] ?? []) as $row)
                      <tr>
                        <td class="fw-semibold">{{ $row['position'] }}</td>
                        <td>{{ $row['admission_number'] }}</td>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['average'] }}</td>
                      </tr>
                    @endforeach
                    @if(empty($payload['top_students']))
                      <tr><td colspan="5" class="text-center text-muted py-4">No data.</td></tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="settings-card h-100">
            <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-graph-up-arrow"></i><h5 class="mb-0">Most Improved</h5></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                  <thead class="table-light"><tr><th>Adm No</th><th>Student</th><th>Prev</th><th>Current</th><th>+/-</th></tr></thead>
                  <tbody>
                    @foreach(($payload['most_improved'] ?? []) as $row)
                      <tr>
                        <td>{{ $row['admission_number'] }}</td>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        <td>{{ $row['prev_total'] }}</td>
                        <td>{{ $row['curr_total'] }}</td>
                        <td class="fw-semibold">{{ $row['improvement'] }}</td>
                      </tr>
                    @endforeach
                    @if(empty($payload['most_improved']))
                      <tr><td colspan="5" class="text-center text-muted py-4">No comparison data available.</td></tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection


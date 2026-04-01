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
        <h1 class="mb-1">Subject Performance</h1>
        <p class="text-muted mb-0">Identify the leading subjects in a specific class for an exam.</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.class-sheet') }}">Class Sheet</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.teacher-performance') }}">Teacher Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-5">
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
      <div class="settings-card">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-graph-up"></i><h5 class="mb-0">Subject Rankings</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
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
    @endif
  </div>
</div>
@endsection


@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exam Analytics</div>
        <h1 class="mb-1">Exam Analytics</h1>
        <p class="text-muted mb-0">Generate grade distributions and subject performance.</p>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" required>
              <option value="">Select Exam</option>
              @foreach($exams as $exam)
                <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }} - {{ $exam->academicYear->year ?? '' }} {{ $exam->term ? 'Term ' . $exam->term->name : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Classroom (Optional)</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classrooms</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Subject (Optional)</label>
            <select name="subject_id" class="form-select">
              <option value="">All Subjects</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-settings-primary">Generate Analytics</button>
          </div>
        </form>
      </div>
    </div>

    @if($analytics)
    <div class="row g-3">
      <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total Students</div><div class="stat-value">{{ $analytics['total_students'] }}</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-label">Average</div><div class="stat-value">{{ $analytics['average'] }}</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-label">Highest</div><div class="stat-value">{{ $analytics['max_mark'] }}</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-label">Lowest</div><div class="stat-value">{{ $analytics['min_mark'] }}</div></div></div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <div class="settings-card h-100">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-graph-up"></i><h5 class="mb-0">Grade Distribution</h5></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern align-middle mb-0">
                <thead class="table-light"><tr><th>Grade</th><th>Count</th></tr></thead>
                <tbody>
                  @foreach($analytics['grade_distribution'] as $grade => $count)
                  <tr><td>{{ $grade ?: 'N/A' }}</td><td>{{ $count }}</td></tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="settings-card h-100">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-bar-chart"></i><h5 class="mb-0">Subject Performance</h5></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern align-middle mb-0">
                <thead class="table-light"><tr><th>Subject</th><th>Average</th><th>Count</th></tr></thead>
                <tbody>
                  @foreach($analytics['subject_performance'] as $perf)
                  <tr><td>{{ $perf['subject'] }}</td><td>{{ number_format($perf['average'], 2) }}</td><td>{{ $perf['count'] }}</td></tr>
                  @endforeach
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

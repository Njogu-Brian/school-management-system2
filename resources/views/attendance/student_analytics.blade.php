@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Attendance</div>
        <h1 class="mb-1">Attendance Analytics: {{ $student->full_name }}</h1>
        <p class="text-muted mb-0">Admission #{{ $student->admission_number }}</p>
      </div>
      <a href="{{ route('students.show', $student) }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Student Profile
      </a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Adjust period and trend window.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start" class="form-control" value="{{ $startDate }}" onchange="this.form.submit()">
          </div>
          <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end" class="form-control" value="{{ $endDate }}" onchange="this.form.submit()">
          </div>
          <div class="col-md-3">
            <label class="form-label">Trend Months</label>
            <input type="number" name="months" class="form-control" value="{{ $months }}" min="1" max="12" onchange="this.form.submit()">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <a href="{{ route('attendance.student-analytics', $student) }}" class="btn btn-ghost-strong w-100">
              <i class="bi bi-arrow-clockwise"></i> Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="settings-card stat-card border-start border-4 border-primary h-100">
          <div class="card-body text-center">
            <h3 class="mb-0 text-primary">{{ number_format($percentage, 1) }}%</h3>
            <small class="text-muted">Attendance Percentage</small>
            <div class="mt-2">
              <small>Period: {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="settings-card stat-card border-start border-4 border-warning h-100">
          <div class="card-body text-center">
            <h3 class="mb-0 text-warning">{{ $consecutive }}</h3>
            <small class="text-muted">Consecutive Absences</small>
            @if($consecutive > 0)
              <div class="mt-2">
                <span class="pill-badge pill-danger">Action Required</span>
              </div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="settings-card stat-card border-start border-4 border-info h-100">
          <div class="card-body text-center">
            <h3 class="mb-0 text-info">{{ $student->classroom->name ?? '—' }}</h3>
            <small class="text-muted">Current Class</small>
            @if($student->stream)
              <div class="mt-1">
                <small>{{ $student->stream->name }}</small>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Attendance Trends (Last {{ $months }} Months)</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Month</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Attendance %</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($trends as $trend)
                <tr>
                  <td class="fw-semibold">{{ $trend['month'] }}</td>
                  <td class="text-center"><span class="pill-badge pill-success">{{ $trend['present'] }}</span></td>
                  <td class="text-center"><span class="pill-badge pill-danger">{{ $trend['absent'] }}</span></td>
                  <td class="text-center"><span class="pill-badge pill-warning">{{ $trend['late'] }}</span></td>
                  <td class="text-center">
                    <span class="pill-badge pill-{{ $trend['percentage'] < 75 ? 'danger' : ($trend['percentage'] < 90 ? 'warning' : 'success') }}">
                      {{ number_format($trend['percentage'], 1) }}%
                    </span>
                  </td>
                  <td>
                    @if($trend['percentage'] >= 90)
                      <span class="text-success"><i class="bi bi-check-circle"></i> Excellent</span>
                    @elseif($trend['percentage'] >= 75)
                      <span class="text-warning"><i class="bi bi-exclamation-circle"></i> Good</span>
                    @else
                      <span class="text-danger"><i class="bi bi-x-circle"></i> At Risk</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @if(count($subjectStats) > 0)
    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bi bi-book"></i> Subject-wise Attendance</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Subject</th>
                <th class="text-center">Total</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Attendance %</th>
              </tr>
            </thead>
            <tbody>
              @foreach($subjectStats as $stat)
                <tr>
                  <td class="fw-semibold">{{ $stat['subject']->name }}</td>
                  <td class="text-center">{{ $stat['total'] }}</td>
                  <td class="text-center"><span class="pill-badge pill-success">{{ $stat['present'] }}</span></td>
                  <td class="text-center"><span class="pill-badge pill-danger">{{ $stat['absent'] }}</span></td>
                  <td class="text-center"><span class="pill-badge pill-warning">{{ $stat['late'] }}</span></td>
                  <td class="text-center">
                    <span class="pill-badge pill-{{ $stat['percentage'] < 75 ? 'danger' : ($stat['percentage'] < 90 ? 'warning' : 'success') }}">
                      {{ number_format($stat['percentage'], 1) }}%
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif
  </div>
</div>
@endsection

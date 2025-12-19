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
        <h1 class="mb-1">At-Risk Students (Low Attendance)</h1>
        <p class="text-muted mb-0">Students with attendance below threshold.</p>
      </div>
      <a href="{{ route('attendance.mark.form') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Marking
      </a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Class, stream, threshold, and dates.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class" class="form-select" onchange="this.form.submit()">
              <option value="">All Classes</option>
              @foreach($classes as $id => $name)
                <option value="{{ $id }}" @selected($selectedClass==$id)>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stream</label>
            <select name="stream" class="form-select" {{ $streams->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
              <option value="">All Streams</option>
              @foreach($streams as $id => $name)
                <option value="{{ $id }}" @selected($selectedStream==$id)>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Threshold (%)</label>
            <input type="number" name="threshold" class="form-control" value="{{ $threshold }}" min="0" max="100" step="0.1" onchange="this.form.submit()">
          </div>
          <div class="col-md-2">
            <label class="form-label">Start Date</label>
            <input type="date" name="start" class="form-control" value="{{ $startDate }}" onchange="this.form.submit()">
          </div>
          <div class="col-md-2">
            <label class="form-label">End Date</label>
            <input type="date" name="end" class="form-control" value="{{ $endDate }}" onchange="this.form.submit()">
          </div>
        </form>
      </div>
    </div>

    @if($atRiskStudents->count() > 0)
    <div class="settings-card">
      <div class="card-header bg-warning d-flex justify-content-between align-items-center">
        <h5 class="mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle"></i>
          {{ $atRiskStudents->count() }} Student(s) Below {{ $threshold }}% Attendance
        </h5>
        <span class="pill-badge pill-dark">{{ $startDate }} - {{ $endDate }}</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Admission #</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Stream</th>
                <th class="text-center">Attendance %</th>
                <th class="text-center">Present Days</th>
                <th class="text-center">Total Days</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($atRiskStudents as $index => $item)
                @php
                  $student = $item['student'];
                  $percentage = $item['percentage'];
                  $presentDays = $item['present_days'];
                  $totalDays = $item['total_days'];
                @endphp
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td class="fw-semibold">{{ $student->admission_number }}</td>
                  <td>
                    <div>{{ $student->full_name }}</div>
                    <small class="text-muted">{{ $student->gender }}</small>
                  </td>
                  <td>{{ $student->classroom->name ?? '—' }}</td>
                  <td>{{ $student->stream->name ?? '—' }}</td>
                  <td class="text-center">
                    <span class="pill-badge pill-{{ $percentage < 50 ? 'danger' : ($percentage < 75 ? 'warning' : 'info') }} fs-6">
                      {{ number_format($percentage, 1) }}%
                    </span>
                  </td>
                  <td class="text-center">{{ $presentDays }}</td>
                  <td class="text-center">{{ $totalDays }}</td>
                  <td class="text-end">
                    <a href="{{ route('attendance.student-analytics', $student) }}" class="btn btn-sm btn-ghost-strong">
                      <i class="bi bi-graph-up"></i> View Analytics
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @else
      <div class="alert alert-soft border-0">
        <i class="bi bi-check-circle"></i> No students found below the {{ $threshold }}% threshold. Great attendance!
      </div>
    @endif
  </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">At-Risk Students (Low Attendance)</h2>
      <small class="text-muted">Students with attendance below threshold</small>
    </div>
    <a href="{{ route('attendance.mark.form') }}" class="btn btn-outline-primary">
      <i class="bi bi-arrow-left"></i> Back to Marking
    </a>
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
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
  <div class="card">
    <div class="card-header bg-warning text-dark">
      <h5 class="mb-0">
        <i class="bi bi-exclamation-triangle"></i> 
        {{ $atRiskStudents->count() }} Student(s) Below {{ $threshold }}% Attendance
      </h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
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
                  <span class="badge bg-{{ $percentage < 50 ? 'danger' : ($percentage < 75 ? 'warning' : 'info') }} fs-6">
                    {{ number_format($percentage, 1) }}%
                  </span>
                </td>
                <td class="text-center">{{ $presentDays }}</td>
                <td class="text-center">{{ $totalDays }}</td>
                <td class="text-end">
                  <a href="{{ route('attendance.student-analytics', $student) }}" class="btn btn-sm btn-outline-primary">
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
    <div class="alert alert-success">
      <i class="bi bi-check-circle"></i> No students found below the {{ $threshold }}% threshold. Great attendance!
    </div>
  @endif
</div>
@endsection


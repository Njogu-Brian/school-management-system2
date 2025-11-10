@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Consecutive Absences</h2>
      <small class="text-muted">Students with multiple consecutive days of absence</small>
    </div>
    <div class="d-flex gap-2">
      <form action="{{ route('attendance.notify-consecutive') }}" method="POST" class="d-inline" onsubmit="return confirm('Send SMS notifications to parents of students with consecutive absences?')">
        @csrf
        <input type="hidden" name="threshold" value="{{ $threshold }}">
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-bell"></i> Notify Parents
        </button>
      </form>
      <a href="{{ route('attendance.mark.form') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Class</label>
          <select name="class" class="form-select" onchange="this.form.submit()">
            <option value="">All Classes</option>
            @foreach($classes as $id => $name)
              <option value="{{ $id }}" @selected($selectedClass==$id)>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Stream</label>
          <select name="stream" class="form-select" {{ $streams->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
            <option value="">All Streams</option>
            @foreach($streams as $id => $name)
              <option value="{{ $id }}" @selected($selectedStream==$id)>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Minimum Consecutive Days</label>
          <input type="number" name="threshold" class="form-control" value="{{ $threshold }}" min="1" onchange="this.form.submit()">
        </div>
      </form>
    </div>
  </div>

  @if($students->count() > 0)
  <div class="card">
    <div class="card-header bg-danger text-white">
      <h5 class="mb-0">
        <i class="bi bi-calendar-x"></i> 
        {{ $students->count() }} Student(s) with {{ $threshold }}+ Consecutive Absences
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
              <th class="text-center">Consecutive Days</th>
              <th>Parent Contact</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($students as $index => $item)
              @php
                $student = $item['student'];
                $consecutive = $item['consecutive_absences'];
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
                  <span class="badge bg-danger fs-6">{{ $consecutive }} day(s)</span>
                </td>
                <td>
                  @if($student->parent)
                    <div class="small">
                      @if($student->parent->guardian_phone)
                        <div><i class="bi bi-phone"></i> {{ $student->parent->guardian_phone }}</div>
                      @endif
                      @if($student->parent->guardian_email)
                        <div><i class="bi bi-envelope"></i> {{ $student->parent->guardian_email }}</div>
                      @endif
                    </div>
                  @else
                    <span class="text-muted">No parent info</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('attendance.student-analytics', $student) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-graph-up"></i> Analytics
                  </a>
                  <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-person"></i> Profile
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
      <i class="bi bi-check-circle"></i> No students found with {{ $threshold }}+ consecutive absences.
    </div>
  @endif
</div>
@endsection


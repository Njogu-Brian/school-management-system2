@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">{{ $exam->name }}</h2>
      <small class="text-muted">Exam details and statistics</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('academics.exams.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <a href="{{ route('academics.exams.edit', $exam->id) }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil"></i> Edit
      </a>
      @if($exam->can_enter_marks)
        <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" class="btn btn-success">
          <i class="bi bi-pencil-square"></i> Enter Marks
        </a>
      @endif
    </div>
  </div>

  {{-- Statistics --}}
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Total Students</h6>
          <h3 class="mb-0">{{ $stats['total_students'] ?? 0 }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Marks Entered</h6>
          <h3 class="mb-0">{{ $stats['marks_entered'] ?? 0 }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Marks Pending</h6>
          <h3 class="mb-0">{{ $stats['marks_pending'] ?? 0 }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Completion Rate</h6>
          <h3 class="mb-0">{{ $stats['completion_rate'] ?? 0 }}%</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Exam Information</h5>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Exam Name</label>
              <div class="fw-semibold">{{ $exam->name }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">Status</label>
              <div>
                <span class="badge bg-{{ $exam->status_badge }} fs-6">{{ ucfirst($exam->status) }}</span>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Type</label>
              <div>
                <span class="badge bg-info">{{ strtoupper($exam->type) }}</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">Modality</label>
              <div>
                <span class="badge bg-secondary">{{ ucfirst($exam->modality) }}</span>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Academic Year</label>
              <div>{{ $exam->academicYear->year ?? '—' }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">Term</label>
              <div>{{ $exam->term->name ?? '—' }}</div>
            </div>
          </div>

          @if($exam->classroom || $exam->subject)
          <div class="row mb-3">
            @if($exam->classroom)
            <div class="col-md-6">
              <label class="text-muted small">Classroom</label>
              <div>{{ $exam->classroom->name }}</div>
            </div>
            @endif
            @if($exam->subject)
            <div class="col-md-6">
              <label class="text-muted small">Subject</label>
              <div>{{ $exam->subject->name }}</div>
            </div>
            @endif
          </div>
          @endif

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="text-muted small">Max Marks</label>
              <div class="h5 text-primary mb-0">{{ number_format($exam->max_marks, 2) }}</div>
            </div>
            <div class="col-md-4">
              <label class="text-muted small">Weight</label>
              <div class="h5 text-info mb-0">{{ number_format($exam->weight, 2) }}%</div>
            </div>
            <div class="col-md-4">
              <label class="text-muted small">Publishing</label>
              <div>
                @if($exam->publish_exam)
                  <span class="badge bg-success">Exam Published</span>
                @endif
                @if($exam->publish_result)
                  <span class="badge bg-info">Result Published</span>
                @endif
              </div>
            </div>
          </div>

          @if($exam->starts_on && $exam->ends_on)
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Start Date</label>
              <div>{{ $exam->starts_on->format('F d, Y') }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">End Date</label>
              <div>{{ $exam->ends_on->format('F d, Y') }}</div>
            </div>
          </div>
          @endif

          @if($exam->creator)
          <div class="row">
            <div class="col-md-6">
              <label class="text-muted small">Created By</label>
              <div>{{ $exam->creator->name }}</div>
              <div class="small text-muted">{{ $exam->created_at->format('M d, Y H:i') }}</div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Quick Actions</h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="{{ route('academics.exams.schedules.index', $exam->id) }}" class="btn btn-outline-primary">
              <i class="bi bi-calendar-week"></i> Manage Schedule
            </a>
            @if($exam->can_enter_marks)
              <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" class="btn btn-outline-success">
                <i class="bi bi-pencil-square"></i> Enter Marks
              </a>
            @endif
            <a href="{{ route('academics.exams.timetable', ['exam_id' => $exam->id]) }}" class="btn btn-outline-info">
              <i class="bi bi-printer"></i> View Timetable
            </a>
            @if($exam->can_publish)
              <form action="{{ route('exams.publish', $exam->id) }}" method="POST" onsubmit="return confirm('Publish results to report cards?')">
                @csrf
                <button type="submit" class="btn btn-success w-100">
                  <i class="bi bi-cloud-upload"></i> Publish Results
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>

      @if($exam->schedules->count() > 0)
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Schedule ({{ $exam->schedules->count() }})</h5>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            @foreach($exam->schedules->take(5) as $schedule)
              <div class="list-group-item px-0">
                <div class="small">
                  <strong>{{ $schedule->subject->name ?? '—' }}</strong>
                  <div class="text-muted">{{ $schedule->exam_date->format('M d, Y') }} at {{ $schedule->start_time }}</div>
                </div>
              </div>
            @endforeach
          </div>
          @if($exam->schedules->count() > 5)
            <div class="text-center mt-2">
              <a href="{{ route('academics.exams.schedules.index', $exam->id) }}" class="small">View all schedules</a>
            </div>
          @endif
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection


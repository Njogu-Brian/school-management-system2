@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">{{ $exam->name }}</h1>
        <p class="text-muted mb-0">Exam details, schedule, and quick actions.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('academics.exams.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="{{ route('academics.exams.edit', $exam->id) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
        @if($exam->can_enter_marks)
          <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" class="btn btn-settings-primary"><i class="bi bi-pencil-square"></i> Enter Marks</a>
        @endif
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="settings-card stat-card border-start border-4 border-primary"><div class="card-body"><div class="text-muted text-uppercase small">Total Students</div><h3 class="mb-0">{{ $stats['total_students'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-3"><div class="settings-card stat-card border-start border-4 border-success"><div class="card-body"><div class="text-muted text-uppercase small">Marks Entered</div><h3 class="mb-0">{{ $stats['marks_entered'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-3"><div class="settings-card stat-card border-start border-4 border-warning"><div class="card-body"><div class="text-muted text-uppercase small">Marks Pending</div><h3 class="mb-0">{{ $stats['marks_pending'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-3"><div class="settings-card stat-card border-start border-4 border-info"><div class="card-body"><div class="text-muted text-uppercase small">Completion Rate</div><h3 class="mb-0">{{ $stats['completion_rate'] ?? 0 }}%</h3></div></div></div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Exam Information</h5></div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6"><span class="text-muted small">Exam Name</span><div class="fw-semibold">{{ $exam->name }}</div></div>
              <div class="col-md-6"><span class="text-muted small">Status</span><div><span class="pill-badge pill-{{ $exam->status_badge }} fs-6">{{ ucfirst($exam->status) }}</span></div></div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6"><span class="text-muted small">Type</span><div><span class="pill-badge pill-info">{{ strtoupper($exam->type) }}</span></div></div>
              <div class="col-md-6"><span class="text-muted small">Modality</span><div><span class="pill-badge pill-secondary">{{ ucfirst($exam->modality) }}</span></div></div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6"><span class="text-muted small">Academic Year</span><div>{{ $exam->academicYear->year ?? '—' }}</div></div>
              <div class="col-md-6"><span class="text-muted small">Term</span><div>{{ $exam->term->name ?? '—' }}</div></div>
            </div>
            @if($exam->classroom || $exam->subject)
            <div class="row mb-3">
              @if($exam->classroom)
              <div class="col-md-6"><span class="text-muted small">Classroom</span><div>{{ $exam->classroom->name }}</div></div>
              @endif
              @if($exam->subject)
              <div class="col-md-6"><span class="text-muted small">Subject</span><div>{{ $exam->subject->name }}</div></div>
              @endif
            </div>
            @endif
            <div class="row mb-3">
              <div class="col-md-4"><span class="text-muted small">Max Marks</span><div class="h5 text-primary mb-0">{{ number_format($exam->max_marks, 2) }}</div></div>
              <div class="col-md-4"><span class="text-muted small">Weight</span><div class="h5 text-info mb-0">{{ number_format($exam->weight, 2) }}%</div></div>
              <div class="col-md-4"><span class="text-muted small">Publishing</span><div>@if($exam->publish_exam)<span class="pill-badge pill-success">Exam Published</span>@endif @if($exam->publish_result)<span class="pill-badge pill-info">Result Published</span>@endif</div></div>
            </div>
            @if($exam->starts_on && $exam->ends_on)
            <div class="row mb-3">
              <div class="col-md-6"><span class="text-muted small">Start Date</span><div>{{ $exam->starts_on->format('F d, Y') }}</div></div>
              <div class="col-md-6"><span class="text-muted small">End Date</span><div>{{ $exam->ends_on->format('F d, Y') }}</div></div>
            </div>
            @endif
            @if($exam->creator)
            <div class="row">
              <div class="col-md-6"><span class="text-muted small">Created By</span><div>{{ $exam->creator->name }}</div><div class="small text-muted">{{ $exam->created_at->format('M d, Y H:i') }}</div></div>
            </div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Quick Actions</h5></div>
          <div class="card-body d-grid gap-2">
            <a href="{{ route('academics.exams.schedules.index', $exam->id) }}" class="btn btn-ghost-strong"><i class="bi bi-calendar-week"></i> Manage Schedule</a>
            @if($exam->can_enter_marks)
              <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" class="btn btn-ghost-strong text-success"><i class="bi bi-pencil-square"></i> Enter Marks</a>
            @endif
            <a href="{{ route('academics.exams.timetable', ['exam_id' => $exam->id]) }}" class="btn btn-ghost-strong text-info"><i class="bi bi-printer"></i> View Timetable</a>
            @if($exam->can_publish)
              <form action="{{ route('exams.publish', $exam->id) }}" method="POST" onsubmit="return confirm('Publish results to report cards?')">
                @csrf
                <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-cloud-upload"></i> Publish Results</button>
              </form>
            @endif
          </div>
        </div>

        @if($exam->schedules->count() > 0)
        <div class="settings-card">
          <div class="card-header"><h5 class="mb-0">Schedule ({{ $exam->schedules->count() }})</h5></div>
          <div class="card-body">
            <div class="list-group list-group-modern">
              @foreach($exam->schedules->take(5) as $schedule)
                <div class="list-group-item">
                  <div class="small fw-semibold">{{ $schedule->subject->name ?? '—' }}</div>
                  <div class="text-muted small">{{ $schedule->exam_date->format('M d, Y') }} • {{ $schedule->start_time }}</div>
                </div>
              @endforeach
            </div>
            @if($exam->schedules->count() > 5)
              <div class="text-center mt-2"><a href="{{ route('academics.exams.schedules.index', $exam->id) }}" class="small">View all schedules</a></div>
            @endif
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

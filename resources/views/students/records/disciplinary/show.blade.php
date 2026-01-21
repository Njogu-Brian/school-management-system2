@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.disciplinary-records.index', $student) }}">Disciplinary Records</a></li>
      <li class="breadcrumb-item active">View Record</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">{{ $disciplinaryRecord->incident_type }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.disciplinary-records.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
      <a href="{{ route('students.disciplinary-records.edit', [$student, $disciplinaryRecord]) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><strong>Incident Date</strong><div>{{ $disciplinaryRecord->incident_date->format('M d, Y') }}</div></div>
        @if($disciplinaryRecord->incident_time)
        <div class="col-md-6"><strong>Incident Time</strong><div>{{ \Carbon\Carbon::parse($disciplinaryRecord->incident_time)->format('h:i A') }}</div></div>
        @endif
        <div class="col-md-6"><strong>Severity</strong><div>
          @php
            $severityColors = ['minor' => 'secondary', 'moderate' => 'warning', 'major' => 'danger', 'severe' => 'dark'];
          @endphp
          <span class="badge bg-{{ $severityColors[$disciplinaryRecord->severity] ?? 'secondary' }}">{{ ucfirst($disciplinaryRecord->severity) }}</span>
        </div></div>
        <div class="col-md-6"><strong>Status</strong><div>
          @if($disciplinaryRecord->resolved)
            <span class="badge bg-success">Resolved</span>
            @if($disciplinaryRecord->resolved_date)
              <small class="text-muted">({{ $disciplinaryRecord->resolved_date->format('M d, Y') }})</small>
            @endif
          @else
            <span class="badge bg-warning">Pending</span>
          @endif
        </div></div>
        <div class="col-md-12"><strong>Description</strong><div>{{ $disciplinaryRecord->description }}</div></div>
        @if($disciplinaryRecord->witnesses)
        <div class="col-md-12"><strong>Witnesses</strong><div>{{ $disciplinaryRecord->witnesses }}</div></div>
        @endif
        @if($disciplinaryRecord->action_taken)
        <div class="col-md-6"><strong>Action Taken</strong><div>{{ ucfirst(str_replace('_', ' ', $disciplinaryRecord->action_taken)) }}</div></div>
        @endif
        @if($disciplinaryRecord->action_date)
        <div class="col-md-6"><strong>Action Date</strong><div>{{ $disciplinaryRecord->action_date->format('M d, Y') }}</div></div>
        @endif
        @if($disciplinaryRecord->action_details)
        <div class="col-md-12"><strong>Action Details</strong><div>{{ $disciplinaryRecord->action_details }}</div></div>
        @endif
        @if($disciplinaryRecord->improvement_plan)
        <div class="col-md-12"><strong>Improvement Plan</strong><div>{{ $disciplinaryRecord->improvement_plan }}</div></div>
        @endif
        @if($disciplinaryRecord->parent_notified)
        <div class="col-md-6"><strong>Parent Notified</strong><div><span class="badge bg-success">Yes</span></div></div>
        @if($disciplinaryRecord->parent_notification_date)
        <div class="col-md-6"><strong>Notification Date</strong><div>{{ $disciplinaryRecord->parent_notification_date->format('M d, Y') }}</div></div>
        @endif
        @endif
        @if($disciplinaryRecord->follow_up_date)
        <div class="col-md-6"><strong>Follow-up Date</strong><div>{{ $disciplinaryRecord->follow_up_date->format('M d, Y') }}</div></div>
        @endif
        @if($disciplinaryRecord->follow_up_notes)
        <div class="col-md-12"><strong>Follow-up Notes</strong><div>{{ $disciplinaryRecord->follow_up_notes }}</div></div>
        @endif
        @if($disciplinaryRecord->reportedBy)
        <div class="col-md-6"><strong>Reported By</strong><div>{{ $disciplinaryRecord->reportedBy->name }}</div></div>
        @endif
        @if($disciplinaryRecord->actionTakenBy)
        <div class="col-md-6"><strong>Action Taken By</strong><div>{{ $disciplinaryRecord->actionTakenBy->name }}</div></div>
        @endif
        <div class="col-md-6"><strong>Created At</strong><div>{{ $disciplinaryRecord->created_at->format('M d, Y h:i A') }}</div></div>
      </div>
    </div>
  </div>
</div>
@endsection


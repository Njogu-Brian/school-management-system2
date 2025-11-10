@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->first_name }} {{ $student->last_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.activities.index', $student) }}">Extracurricular Activities</a></li>
      <li class="breadcrumb-item active">View Activity</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">{{ $activity->activity_name }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.activities.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
      <a href="{{ route('students.activities.edit', [$student, $activity]) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><strong>Activity Type</strong><div><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</span></div></div>
        <div class="col-md-6"><strong>Status</strong><div>
          @if($activity->is_active)
            <span class="badge bg-success">Active</span>
          @else
            <span class="badge bg-secondary">Inactive</span>
          @endif
        </div></div>
        <div class="col-md-6"><strong>Start Date</strong><div>{{ $activity->start_date->format('M d, Y') }}</div></div>
        <div class="col-md-6"><strong>End Date</strong><div>{{ $activity->end_date ? $activity->end_date->format('M d, Y') : 'Ongoing' }}</div></div>
        @if($activity->description)
        <div class="col-md-12"><strong>Description</strong><div>{{ $activity->description }}</div></div>
        @endif
        @if($activity->position_role)
        <div class="col-md-6"><strong>Position/Role</strong><div>{{ $activity->position_role }}</div></div>
        @endif
        @if($activity->team_name)
        <div class="col-md-6"><strong>Team Name</strong><div>{{ $activity->team_name }}</div></div>
        @endif
        @if($activity->competition_name)
        <div class="col-md-6"><strong>Competition Name</strong><div>{{ $activity->competition_name }}</div></div>
        @endif
        @if($activity->competition_level)
        <div class="col-md-6"><strong>Competition Level</strong><div>{{ $activity->competition_level }}</div></div>
        @endif
        @if($activity->award_achievement)
        <div class="col-md-6"><strong>Award/Achievement</strong><div>{{ $activity->award_achievement }}</div></div>
        @endif
        @if($activity->achievement_date)
        <div class="col-md-6"><strong>Achievement Date</strong><div>{{ $activity->achievement_date->format('M d, Y') }}</div></div>
        @endif
        @if($activity->achievement_description)
        <div class="col-md-12"><strong>Achievement Description</strong><div>{{ $activity->achievement_description }}</div></div>
        @endif
        @if($activity->community_service_hours)
        <div class="col-md-6"><strong>Community Service Hours</strong><div>{{ $activity->community_service_hours }} hours</div></div>
        @endif
        @if($activity->supervisor)
        <div class="col-md-6"><strong>Supervisor</strong><div>{{ $activity->supervisor->name }}</div></div>
        @endif
        @if($activity->notes)
        <div class="col-md-12"><strong>Notes</strong><div>{{ $activity->notes }}</div></div>
        @endif
        <div class="col-md-6"><strong>Created At</strong><div>{{ $activity->created_at->format('M d, Y h:i A') }}</div></div>
      </div>
    </div>
  </div>
</div>
@endsection


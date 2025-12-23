@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Lesson Plans</div>
        <h1 class="mb-1">{{ $lesson_plan->title }}</h1>
        <p class="text-muted mb-0">Lesson details, outcomes, and linked homework.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="{{ route('academics.lesson-plans.edit', $lesson_plan) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Lesson Plan Details</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="200">Subject</th><td>{{ $lesson_plan->subject->name }}</td></tr>
              <tr><th>Classroom</th><td>{{ $lesson_plan->classroom->name }}</td></tr>
              <tr><th>Planned Date</th><td>{{ $lesson_plan->planned_date->format('l, d M Y') }}</td></tr>
              @if($lesson_plan->actual_date)<tr><th>Actual Date</th><td>{{ $lesson_plan->actual_date->format('l, d M Y') }}</td></tr>@endif
              <tr><th>Status</th><td><span class="pill-badge pill-{{ $lesson_plan->status == 'completed' ? 'success' : ($lesson_plan->status == 'in_progress' ? 'warning' : 'info') }}">{{ ucfirst($lesson_plan->status) }}</span></td></tr>
              @if($lesson_plan->substrand)<tr><th>CBC Substrand</th><td>{{ $lesson_plan->substrand->strand->name }} - {{ $lesson_plan->substrand->name }}</td></tr>@endif
            </table>
          </div>
        </div>

        @if($lesson_plan->learning_objectives)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Learning Objectives</h5></div>
          <div class="card-body">
            @if(is_array($lesson_plan->learning_objectives))
              <ul class="mb-0">@foreach($lesson_plan->learning_objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul>
            @else
              <p class="mb-0">{{ $lesson_plan->learning_objectives }}</p>
            @endif
          </div>
        </div>
        @endif

        @if($lesson_plan->learning_outcomes)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Learning Outcomes</h5></div>
          <div class="card-body"><p class="mb-0">{{ $lesson_plan->learning_outcomes }}</p></div>
        </div>
        @endif

        @if($lesson_plan->introduction)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Introduction</h5></div>
          <div class="card-body"><p class="mb-0">{{ $lesson_plan->introduction }}</p></div>
        </div>
        @endif

        @if($lesson_plan->lesson_development)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Lesson Development</h5></div>
          <div class="card-body"><p class="mb-0">{{ $lesson_plan->lesson_development }}</p></div>
        </div>
        @endif

        @if($lesson_plan->assessment)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Assessment</h5></div>
          <div class="card-body"><p class="mb-0">{{ $lesson_plan->assessment }}</p></div>
        </div>
        @endif

        @if($lesson_plan->conclusion)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Conclusion</h5></div>
          <div class="card-body"><p class="mb-0">{{ $lesson_plan->conclusion }}</p></div>
        </div>
        @endif

        @if($lesson_plan->reflection)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Reflection</h5></div>
          <div class="card-body"><p class="mb-0">{{ $lesson_plan->reflection }}</p></div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Information</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Created by:</strong> {{ $lesson_plan->creator->first_name ?? 'N/A' }} {{ $lesson_plan->creator->last_name ?? '' }}</small>
            <small class="text-muted d-block mb-1"><strong>Created:</strong> {{ $lesson_plan->created_at->format('d M Y') }}</small>
            <small class="text-muted d-block"><strong>Duration:</strong> {{ $lesson_plan->duration_minutes }} minutes</small>
          </div>
        </div>

        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge"></i><h5 class="mb-0">Quick Actions</h5></div>
          <div class="card-body d-grid gap-2">
            @if(!$lesson_plan->isApproved() && (is_supervisor() || auth()->user()->hasAnyRole(['Admin', 'Super Admin'])))
              @php
                $canApprove = false;
                if (auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) { $canApprove = true; }
                elseif (is_supervisor()) { $subordinateClassroomIds = get_subordinate_classroom_ids(); $canApprove = in_array($lesson_plan->classroom_id, $subordinateClassroomIds); }
              @endphp
              @if($canApprove)
              <form action="{{ route('academics.lesson-plans.approve', $lesson_plan) }}" method="POST" class="p-0">
                @csrf
                <label class="form-label small mb-1">Approval Notes (Optional)</label>
                <textarea name="approval_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Add any notes about this lesson plan..."></textarea>
                <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-check-circle"></i> Approve Lesson Plan</button>
              </form>
              @endif
            @endif

            @if($lesson_plan->isApproved())
              <div class="alert alert-soft border-0 mb-2">
                <div class="d-flex align-items-center gap-2"><i class="bi bi-check-circle text-success"></i><strong>Approved</strong></div>
                <small class="text-muted d-block">By: {{ $lesson_plan->approver->full_name ?? 'N/A' }}</small>
                <small class="text-muted d-block">On: {{ $lesson_plan->approved_at->format('d M Y, H:i') }}</small>
                @if($lesson_plan->approval_notes)<small class="text-muted d-block">Notes: {{ $lesson_plan->approval_notes }}</small>@endif
              </div>
            @endif

            @can('homework.create')
            <a href="{{ route('academics.lesson-plans.assign-homework', $lesson_plan) }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Assign Homework</a>
            @endcan
            @can('lesson_plans.export_pdf')
            <a href="{{ route('academics.lesson-plans.export-pdf', $lesson_plan) }}" class="btn btn-ghost-strong text-danger" target="_blank"><i class="bi bi-file-pdf"></i> Export PDF</a>
            @endcan
            @can('lesson_plans.export_excel')
            <a href="{{ route('academics.lesson-plans.export-excel', $lesson_plan) }}" class="btn btn-ghost-strong text-success"><i class="bi bi-file-excel"></i> Export Excel</a>
            @endcan
          </div>
        </div>

    @if(isset($homework) && $homework && $homework->count() > 0)
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-check"></i><h5 class="mb-0">Linked Homework ({{ $homework->count() }})</h5></div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              @foreach($homework as $hw)
                <li class="mb-2"><a href="{{ route('academics.homework.show', $hw) }}" class="text-reset text-decoration-none"><strong>{{ $hw->title }}</strong><br><small class="text-muted">Due: {{ $hw->due_date->format('d M Y') }}</small></a></li>
              @endforeach
            </ul>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

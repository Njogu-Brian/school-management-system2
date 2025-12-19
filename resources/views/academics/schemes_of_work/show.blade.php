@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Schemes of Work</div>
        <h1 class="mb-1">{{ $schemes_of_work->title }}</h1>
        <p class="text-muted mb-0">Scheme details, progress, and linked lesson plans.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        @can('schemes_of_work.export_pdf')
        <a href="{{ route('academics.schemes-of-work.export-pdf', $schemes_of_work) }}" class="btn btn-ghost-strong text-danger" target="_blank"><i class="bi bi-file-pdf"></i> Export PDF</a>
        @endcan
        @can('schemes_of_work.export_excel')
        <a href="{{ route('academics.schemes-of-work.export-excel', $schemes_of_work) }}" class="btn btn-ghost-strong text-success"><i class="bi bi-file-excel"></i> Export Excel</a>
        @endcan
        @can('schemes_of_work.edit')
        <a href="{{ route('academics.schemes-of-work.edit', $schemes_of_work) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        <a href="{{ route('academics.schemes-of-work.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Scheme Details</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="200">Subject</th><td>{{ $schemes_of_work->subject->name }}</td></tr>
              <tr><th>Classroom</th><td>{{ $schemes_of_work->classroom->name }}</td></tr>
              <tr><th>Academic Year</th><td>{{ $schemes_of_work->academicYear->year }}</td></tr>
              <tr><th>Term</th><td>{{ $schemes_of_work->term->name }}</td></tr>
              <tr><th>Status</th><td><span class="pill-badge pill-{{ $schemes_of_work->status == 'active' ? 'success' : ($schemes_of_work->status == 'completed' ? 'info' : 'warning') }}">{{ ucfirst($schemes_of_work->status) }}</span></td></tr>
              <tr><th>Progress</th><td><div class="progress" style="height: 10px;"><div class="progress-bar" role="progressbar" style="width: {{ $schemes_of_work->progress_percentage }}%"></div></div><small class="text-muted">{{ $schemes_of_work->lessons_completed }} of {{ $schemes_of_work->total_lessons }} lessons completed ({{ $schemes_of_work->progress_percentage }}%)</small></td></tr>
              @if($schemes_of_work->description)<tr><th>Description</th><td>{{ $schemes_of_work->description }}</td></tr>@endif
              @if($schemes_of_work->general_remarks)<tr><th>Remarks</th><td>{{ $schemes_of_work->general_remarks }}</td></tr>@endif
            </table>
          </div>
        </div>

        @if($schemes_of_work->lessonPlans->count() > 0)
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-check"></i><h5 class="mb-0">Lesson Plans ({{ $schemes_of_work->lessonPlans->count() }})</h5></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Title</th><th>Planned Date</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                  @foreach($schemes_of_work->lessonPlans as $plan)
                  <tr>
                    <td>{{ $plan->title }}</td>
                    <td>{{ $plan->planned_date->format('d M Y') }}</td>
                    <td><span class="pill-badge pill-{{ $plan->status == 'completed' ? 'success' : 'info' }}">{{ ucfirst($plan->status) }}</span></td>
                    <td class="text-end"><a href="{{ route('academics.lesson-plans.show', $plan) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a></td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge"></i><h5 class="mb-0">Quick Actions</h5></div>
          <div class="card-body d-grid gap-2">
            @if(!$schemes_of_work->isApproved() && Auth::user()->hasAnyRole(['Admin', 'Super Admin']))
            <form action="{{ route('academics.schemes-of-work.approve', $schemes_of_work) }}" method="POST" class="p-0">
              @csrf
              <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-check-circle"></i> Approve Scheme</button>
            </form>
            @endif
            <a href="{{ route('academics.lesson-plans.create', ['scheme_of_work_id' => $schemes_of_work->id]) }}" class="btn btn-ghost-strong"><i class="bi bi-plus-circle"></i> Add Lesson Plan</a>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Metadata</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Created by:</strong> {{ $schemes_of_work->creator->full_name ?? 'N/A' }}</small>
            <small class="text-muted d-block mb-1"><strong>Created:</strong> {{ $schemes_of_work->created_at->format('d M Y') }}</small>
            @if($schemes_of_work->isApproved())
              <small class="text-muted d-block mb-1"><strong>Approved by:</strong> {{ $schemes_of_work->approver->full_name ?? 'N/A' }}</small>
              <small class="text-muted d-block"><strong>Approved:</strong> {{ $schemes_of_work->approved_at->format('d M Y') }}</small>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

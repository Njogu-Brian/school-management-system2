@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Subject Details</h1>
        <p class="text-muted mb-0">Information and classroom assignments.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('academics.subjects.index') }}" class="btn btn-ghost-strong">
          <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="{{ route('academics.subjects.edit', $subject) }}" class="btn btn-settings-primary">
          <i class="bi bi-pencil"></i> Edit
        </a>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-book"></i>
            <h5 class="mb-0">Subject Information</h5>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6"><strong>Code:</strong> {{ $subject->code }}</div>
              <div class="col-md-6"><strong>Name:</strong> {{ $subject->name }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Group:</strong>
                @if($subject->group)
                  <span class="pill-badge pill-info">{{ $subject->group->name }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </div>
              <div class="col-md-6">
                <strong>Level:</strong>
                @if($subject->level)
                  <span class="pill-badge pill-secondary">{{ $subject->level }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6"><strong>Learning Area:</strong> {{ $subject->learning_area ?? '—' }}</div>
              <div class="col-md-6">
                <strong>Type:</strong>
                @if($subject->is_optional)
                  <span class="pill-badge pill-warning">Optional</span>
                @else
                  <span class="pill-badge pill-success">Mandatory</span>
                @endif
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <strong>Status:</strong>
                @if($subject->is_active)
                  <span class="pill-badge pill-success">Active</span>
                @else
                  <span class="pill-badge pill-muted">Inactive</span>
                @endif
              </div>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-building"></i>
            <h5 class="mb-0">Classroom Assignments</h5>
          </div>
          <div class="card-body">
            @if($subject->classroomSubjects->count() > 0)
              <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Classroom</th>
                      <th>Stream</th>
                      <th>Teacher</th>
                      <th>Academic Year</th>
                      <th>Term</th>
                      <th>Type</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($subject->classroomSubjects as $assignment)
                      <tr>
                        <td>{{ $assignment->classroom->name }}</td>
                        <td>{{ $assignment->stream->name ?? '—' }}</td>
                        <td>{{ $assignment->teacher->full_name ?? '—' }}</td>
                        <td>{{ $assignment->academicYear->year ?? '—' }}</td>
                        <td>{{ $assignment->term->name ?? '—' }}</td>
                        <td>
                          @if($assignment->is_compulsory)
                            <span class="pill-badge pill-success">Compulsory</span>
                          @else
                            <span class="pill-badge pill-warning">Optional</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted mb-0">No classroom assignments yet.</p>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart"></i>
            <h5 class="mb-0">Statistics</h5>
          </div>
          <div class="card-body">
            <div class="mb-3"><strong>Classrooms:</strong> <span class="pill-badge pill-primary">{{ $subject->classrooms->count() }}</span></div>
            <div class="mb-0"><strong>Teachers:</strong> <span class="pill-badge pill-secondary">{{ $subject->teachers->count() }}</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

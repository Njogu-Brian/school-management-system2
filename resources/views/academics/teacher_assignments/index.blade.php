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
        <h1 class="mb-1">Teacher Assignments</h1>
        <p class="text-muted mb-0">Assign streams, subjects, class teacher, and assistant teacher roles in one place.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('academics.assign-teachers') }}" class="btn btn-ghost-strong btn-sm">
          <i class="bi bi-person-check"></i> Assign Class Teachers
        </a>
        <a href="{{ route('academics.subjects.teacher-assignments') }}" class="btn btn-ghost-strong btn-sm">
          <i class="bi bi-person-lines-fill"></i> Subject Teacher Map
        </a>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-people"></i> Select a Teacher</h5>
      </div>
      <div class="card-body">
        @if($staffTeachers->isEmpty())
          <div class="alert alert-info mb-0">No staff with a teaching role found.</div>
        @else
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Staff ID</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($staffTeachers as $teacher)
                  <tr>
                    <td class="fw-semibold">{{ $teacher->full_name }}</td>
                    <td>{{ $teacher->staff_id ?? '—' }}</td>
                    <td class="text-end">
                      <a href="{{ route('academics.teacher-assignments.edit', $teacher->id) }}" class="btn btn-sm btn-settings-primary">
                        <i class="bi bi-pencil-square"></i> Manage Assignments
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

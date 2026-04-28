@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Assign Class Teachers</h1>
        <p class="text-muted mb-0">Each classroom has one class teacher (homeroom). Subject teachers are assigned separately in Subject Teacher Map.</p>
      </div>
    </div>

    <div class="settings-card mb-3 border-danger border-opacity-25">
      <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
        <div>
          <h6 class="mb-1 text-danger"><i class="bi bi-exclamation-triangle"></i> Remove all assignments listed below</h6>
          <p class="text-muted small mb-0">
            This clears <strong>every</strong> class teacher assignment on this page.
            It does <strong>not</strong> change Subject Teacher Map (subject–class slots) and does not affect stream teacher pivots.
          </p>
        </div>
        <form method="POST" action="{{ route('academics.assign-teachers.clear') }}" class="flex-shrink-0" onsubmit="return confirm('Clear ALL class teacher assignments?');">
          @csrf
          <input type="hidden" name="confirm_clear" value="CLEARALL">
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Clear all class teacher assignments
          </button>
        </form>
      </div>
    </div>

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        @foreach($errors->all() as $err)
          <div>{{ $err }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-person-check"></i> Class Teacher Assignments</h5>
        <a href="{{ route('academics.subjects.teacher-assignments') }}" class="btn btn-sm btn-ghost-strong">
          <i class="bi bi-person-lines-fill"></i> Subject Teacher Map
        </a>
      </div>
      <div class="card-body">
        @if($classrooms->isEmpty())
          <div class="alert alert-info alert-soft border-0 mb-0">
            <i class="bi bi-info-circle"></i> No classrooms found. <a href="{{ route('academics.classrooms.index') }}">Create classrooms</a> first.
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-modern table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Classroom</th>
                  <th>Stream</th>
                  <th>Current Class Teacher</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($classrooms as $classroom)
                  @php
                    $streams = $classroom->allStreams();
                    $rows = $streams->isEmpty() ? collect([(object) ['id' => null, 'name' => '— No streams —']]) : $streams;
                  @endphp

                  @foreach($rows as $stream)
                    @php
                      $key = $classroom->id . ':' . (($stream->id ?? null) === null ? 'null' : $stream->id);
                      $assignedStaffId = $assignmentMap[$key] ?? null;
                      $assignedStaff = $assignedStaffId ? $staffTeachers->firstWhere('id', $assignedStaffId) : null;
                      $modalId = 'assignClassTeacherModal' . $classroom->id . '_' . (($stream->id ?? null) === null ? 'null' : $stream->id);
                    @endphp
                    <tr>
                      <td class="fw-semibold">{{ $classroom->name }}</td>
                      <td>{{ $stream->name ?? '—' }}</td>
                      <td>
                        @if($assignedStaff)
                          <span class="pill-badge pill-success">{{ $assignedStaff->full_name }}</span>
                        @else
                          <span class="text-muted">Unassigned</span>
                        @endif
                      </td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                          <i class="bi bi-pencil"></i> Assign
                        </button>
                      </td>
                    </tr>

                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content settings-card mb-0">
                          <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title">Assign Class Teacher — {{ $classroom->name }} @if(($stream->id ?? null) !== null) ({{ $stream->name }}) @endif</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <form action="{{ route('academics.classrooms.assign-class-teacher', $classroom->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="stream_id" value="{{ $stream->id ?? '' }}">
                            <div class="modal-body">
                              <label class="form-label">Select Staff (Teacher)</label>
                              <select name="staff_id" class="form-select">
                                <option value="">— Unassigned —</option>
                                @foreach($staffTeachers as $st)
                                  <option value="{{ $st->id }}" @selected((string) $assignedStaffId === (string) $st->id)>
                                    {{ $st->full_name }}
                                  </option>
                                @endforeach
                              </select>
                              <small class="text-muted">
                                If the classroom has streams, each stream can have its own class teacher (streams are treated as separate classes).
                              </small>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                              <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-settings-primary">Save</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  @endforeach
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

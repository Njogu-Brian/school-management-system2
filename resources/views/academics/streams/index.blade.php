@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .class-streams-page .grade-row {
            border-bottom: 1px solid var(--bs-border-color);
            padding: 1.25rem 0;
        }
        .class-streams-page .grade-row:last-child { border-bottom: none; }
        .class-streams-page .grade-label {
            font-weight: 600;
            font-size: 0.95rem;
            color: #6c757d;
            min-width: 5.5rem;
            padding-top: 0.35rem;
        }
        .class-streams-page .stream-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 1rem 1.1rem;
            background: #fff;
            min-width: 200px;
            flex: 1 1 200px;
            max-width: 280px;
            position: relative;
        }
        .class-streams-page .stream-card-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.35rem;
            color: #1a1a2e;
        }
        .class-streams-page .stream-card-teacher {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.15rem;
        }
        .class-streams-page .stream-card-teacher strong { color: #495057; font-weight: 500; }
        .class-streams-page .stream-card-meta {
            font-size: 0.8rem;
            color: #868e96;
        }
        .class-streams-page .stream-card-actions {
            position: absolute;
            top: 0.65rem;
            right: 0.65rem;
            display: flex;
            gap: 0.25rem;
        }
        .class-streams-page .stream-card-actions .btn {
            padding: 0.15rem 0.4rem;
            font-size: 0.85rem;
        }
        .class-streams-page .add-stream-card {
            border: 2px dashed var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            min-width: 140px;
            flex: 0 0 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6f42c1;
            background: rgba(111, 66, 193, 0.04);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
        }
        .class-streams-page .add-stream-card:hover {
            background: rgba(111, 66, 193, 0.1);
            border-color: #6f42c1;
            color: #5a32a3;
        }
        .class-streams-page .nav-tabs-class-streams .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            padding: 0.75rem 1.25rem;
        }
        .class-streams-page .nav-tabs-class-streams .nav-link.active {
            color: #6f42c1;
            border-bottom-color: #6f42c1;
            background: transparent;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
<div class="settings-page class-streams-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Class Streams</h1>
        <p class="text-muted mb-0">Create classes and streams, assign class teachers, and manage student placement.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @if(Route::has('students.bulk.assign-streams'))
          <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-ghost-strong">
            <i class="bi bi-people"></i> Assign Students to Streams
          </a>
        @endif
        <a href="{{ route('academics.classrooms.create') }}" class="btn btn-ghost-strong">
          <i class="bi bi-building"></i> Add Class
        </a>
        <a href="{{ route('academics.streams.create') }}" class="btn btn-settings-primary">
          <i class="bi bi-plus-circle"></i> Add Stream
        </a>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <ul class="nav nav-tabs nav-tabs-class-streams mb-3">
      <li class="nav-item">
        <span class="nav-link active">Streams</span>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('academics.classrooms.index') }}">Classes</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('academics.teacher-assignments.index') }}">Teacher Assignments</a>
      </li>
    </ul>

    <div class="settings-card">
      <div class="card-body">
        @forelse($classrooms as $classroom)
          @php
            $streams = $classroom->allStreams();
          @endphp
          <div class="grade-row d-flex flex-column flex-md-row gap-3">
            <div class="grade-label">{{ $classroom->name }}</div>
            <div class="d-flex flex-wrap gap-3 flex-grow-1 align-items-stretch">
              @if($streams->isEmpty())
                @php
                  $slotKey = $classroom->id . ':null';
                  $ct = $classTeacherMap[$slotKey] ?? null;
                  $at = $assistantMap[$slotKey] ?? null;
                  $studentCount = $classroom->students_count ?? 0;
                @endphp
                <div class="stream-card">
                  <div class="stream-card-actions">
                    <button type="button" class="btn btn-sm btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#editClassModal{{ $classroom->id }}" title="Edit">
                      <i class="bi bi-pencil text-primary"></i>
                    </button>
                  </div>
                  <div class="stream-card-title">{{ $classroom->name }}</div>
                  <div class="stream-card-teacher">
                    Class Teacher:
                    @if($ct)
                      <strong>{{ $ct->full_name }}</strong>
                    @else
                      <span class="text-muted fst-italic">No Class Teacher Assigned</span>
                    @endif
                  </div>
                  @if($at)
                    <div class="stream-card-teacher">
                      Assistant: <strong>{{ $at->full_name }}</strong>
                    </div>
                  @endif
                  <div class="stream-card-meta">{{ $studentCount }} student(s)</div>
                </div>

                <a href="{{ route('academics.streams.create') }}?classroom_id={{ $classroom->id }}" class="add-stream-card text-center">
                  <span><i class="bi bi-plus-lg"></i><br><small>Add Stream</small></span>
                </a>

                {{-- Modal: classroom without streams --}}
                <div class="modal fade" id="editClassModal{{ $classroom->id }}" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content settings-card mb-0">
                      <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title">Edit Class Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form action="{{ route('academics.classrooms.quick-homeroom', $classroom->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label text-muted small">Class</label>
                            <input type="text" class="form-control" value="{{ $classroom->name }}" readonly>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Assigned class teacher</label>
                            <select name="class_teacher_staff_id" class="form-select">
                              <option value="">— No Class Teacher —</option>
                              @foreach($staffTeachers as $st)
                                <option value="{{ $st->id }}" @selected($ct && (int) $ct->id === (int) $st->id)>{{ $st->full_name }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Assistant class teacher</label>
                            <select name="assistant_teacher_staff_id" class="form-select">
                              <option value="">— No Assistant —</option>
                              @foreach($staffTeachers as $st)
                                <option value="{{ $st->id }}" @selected($at && (int) $at->id === (int) $st->id)>{{ $st->full_name }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('academics.classrooms.edit', $classroom->id) }}" class="btn btn-sm btn-ghost-strong">Full class settings</a>
                            @if(Route::has('students.bulk.assign-streams'))
                              <a href="{{ route('students.bulk.assign-streams', ['classroom_id' => $classroom->id]) }}" class="btn btn-sm btn-ghost-strong">Assign students</a>
                            @endif
                          </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-settings-primary">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              @else
                @foreach($streams as $stream)
                  @php
                    $slotKey = $classroom->id . ':' . $stream->id;
                    $ct = $classTeacherMap[$slotKey] ?? null;
                    $at = $assistantMap[$slotKey] ?? null;
                    $studentCount = $studentCountsByStream[$stream->id] ?? 0;
                    $displayName = $classroom->name . ' ' . $stream->name;
                    $modalId = 'editStreamModal' . $stream->id;
                  @endphp
                  <div class="stream-card">
                    <div class="stream-card-actions">
                      <button type="button" class="btn btn-sm btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="Edit">
                        <i class="bi bi-pencil text-primary"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-ghost-strong" onclick="deleteStream({{ $stream->id }})" title="Delete">
                        <i class="bi bi-trash text-danger"></i>
                      </button>
                      <form id="delete-form-{{ $stream->id }}" action="{{ route('academics.streams.destroy', $stream->id) }}" method="POST" class="d-none">
                        @csrf @method('DELETE')
                      </form>
                    </div>
                    <div class="stream-card-title">{{ $displayName }}</div>
                    <div class="stream-card-teacher">
                      Class Teacher:
                      @if($ct)
                        <strong>{{ $ct->full_name }}</strong>
                      @else
                        <span class="text-muted fst-italic">No Class Teacher Assigned</span>
                      @endif
                    </div>
                    @if($at)
                      <div class="stream-card-teacher">
                        Assistant: <strong>{{ $at->full_name }}</strong>
                      </div>
                    @endif
                    <div class="stream-card-meta">{{ $studentCount }} student(s) in this stream</div>
                  </div>

                  <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content settings-card mb-0">
                        <div class="modal-header border-0 pb-0">
                          <h5 class="modal-title">Edit Stream Details</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('academics.streams.quick-update', $stream->id) }}" method="POST">
                          @csrf
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label text-muted small">Class</label>
                              <input type="text" class="form-control" value="{{ $classroom->name }}" readonly>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Stream name <span class="text-danger">*</span></label>
                              <input type="text" name="name" class="form-control" value="{{ $stream->name }}" required placeholder="e.g. LOVE, PEACE">
                              <small class="text-muted">Displayed as "{{ $classroom->name }} [stream name]"</small>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Assigned class teacher</label>
                              <select name="class_teacher_staff_id" class="form-select">
                                <option value="">— No Class Teacher —</option>
                                @foreach($staffTeachers as $st)
                                  <option value="{{ $st->id }}" @selected($ct && (int) $ct->id === (int) $st->id)>{{ $st->full_name }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Assistant class teacher</label>
                              <select name="assistant_teacher_staff_id" class="form-select">
                                <option value="">— No Assistant —</option>
                                @foreach($staffTeachers as $st)
                                  <option value="{{ $st->id }}" @selected($at && (int) $at->id === (int) $st->id)>{{ $st->full_name }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                              <a href="{{ route('academics.teacher-assignments.index') }}" class="btn btn-sm btn-ghost-strong">Subject assignments</a>
                              @if(Route::has('students.bulk.assign-streams'))
                                <a href="{{ route('students.bulk.assign-streams', ['classroom_id' => $classroom->id]) }}" class="btn btn-sm btn-ghost-strong">Assign students</a>
                              @endif
                            </div>
                          </div>
                          <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-settings-primary">Save Changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @endforeach

                <a href="{{ route('academics.streams.create') }}?classroom_id={{ $classroom->id }}" class="add-stream-card text-center">
                  <span><i class="bi bi-plus-lg"></i><br><small>Add Stream</small></span>
                </a>
              @endif
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-5">
            <i class="bi bi-diagram-3 display-4 d-block mb-2 opacity-50"></i>
            <p class="mb-2">No classes found yet.</p>
            <a href="{{ route('academics.classrooms.create') }}" class="btn btn-settings-primary btn-sm">Create your first class</a>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<script>
function deleteStream(id) {
    if (confirm('Delete this stream? Students will be unassigned from the stream (class unchanged).')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection

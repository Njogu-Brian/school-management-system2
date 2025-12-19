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
        <h1 class="mb-1">Classroom Management</h1>
        <p class="text-muted mb-0">Manage classes, streams, teachers, and student assignments.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @if(Route::has('students.bulk.assign-streams'))
          <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-ghost-strong">
            <i class="bi bi-people"></i> Bulk Assign Students to Streams
          </a>
        @endif
        <a href="{{ route('academics.classrooms.create') }}" class="btn btn-settings-primary">
          <i class="bi bi-plus-circle"></i> Add New Classroom
        </a>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-primary h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase fw-semibold small">Total Classes</div>
            <h3 class="mb-0">{{ $classrooms->count() }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-success h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase fw-semibold small">Total Students</div>
            <h3 class="mb-0">{{ $classrooms->sum(fn($c) => $c->students->count()) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-info h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase fw-semibold small">Total Streams</div>
            <h3 class="mb-0">{{ $classrooms->sum(fn($c) => $c->streams->count()) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-warning h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase fw-semibold small">Mapped Classes</div>
            <h3 class="mb-0">{{ $classrooms->filter(fn($c) => $c->nextClass || $c->is_alumni)->count() }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Classrooms</h5>
          <p class="text-muted small mb-0">Types, streams, teachers, and promotion mapping.</p>
        </div>
        <span class="input-chip">{{ $classrooms->count() }} total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Class Name</th>
                <th>Type</th>
                <th>Next Class</th>
                <th>Students</th>
                <th>Streams</th>
                <th>Teachers</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($classrooms as $classroom)
              <tr>
                <td class="fw-semibold">{{ $classroom->name }}</td>
                <td>
                  @if($classroom->is_beginner)
                    <span class="pill-badge pill-info">Beginner</span>
                  @endif
                  @if($classroom->is_alumni)
                    <span class="pill-badge pill-warning">Alumni</span>
                  @endif
                  @if(!$classroom->is_beginner && !$classroom->is_alumni)
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($classroom->is_alumni)
                    <span class="text-muted">Graduation</span>
                  @elseif($classroom->nextClass)
                    <span class="text-success d-inline-flex align-items-center gap-1">
                      <i class="bi bi-arrow-right"></i> {{ $classroom->nextClass->name }}
                    </span>
                    @if($classroom->previousClasses->count() > 0)
                      <div class="small text-muted">
                        <i class="bi bi-arrow-left"></i> From: {{ $classroom->previousClasses->pluck('name')->join(', ') }}
                      </div>
                    @endif
                  @else
                    <span class="text-danger">Not Mapped</span>
                  @endif
                </td>
                <td>
                  <span class="pill-badge pill-primary">{{ $classroom->students->count() }}</span>
                  @if($classroom->students->count() > 0)
                    <div class="small text-muted">
                      <a href="{{ route('students.index', ['classroom_id' => $classroom->id]) }}" class="text-reset text-decoration-none">View Students</a>
                    </div>
                  @endif
                </td>
                <td>
                  @if($classroom->streams->count())
                    <div class="d-flex flex-wrap gap-1">
                      @foreach($classroom->streams as $stream)
                        <span class="pill-badge pill-info">{{ $stream->name }}</span>
                      @endforeach
                    </div>
                  @else
                    <span class="text-muted">No Stream Assigned</span>
                  @endif
                </td>
                <td>
                  @php $allTeachers = $classroom->allTeachers(); @endphp
                  @if($allTeachers->count())
                    <div class="d-flex flex-wrap gap-1">
                      @foreach($allTeachers as $teacher)
                        <span class="pill-badge pill-success">{{ $teacher->staff?->first_name ?? $teacher->name }} {{ $teacher->staff?->last_name }}</span>
                      @endforeach
                    </div>
                  @else
                    <span class="text-muted">Not Assigned</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1 flex-wrap">
                    @if(Route::has('students.bulk.assign-streams'))
                      <a href="{{ route('students.bulk.assign-streams', ['classroom_id' => $classroom->id]) }}" class="btn btn-sm btn-ghost-strong text-success" title="Assign Students to Streams">
                        <i class="bi bi-people"></i>
                      </a>
                    @endif
                    <a href="{{ route('academics.classrooms.edit', $classroom->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    @if($classroom->students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni))
                      <a href="{{ route('academics.promotions.show', $classroom) }}" class="btn btn-sm btn-ghost-strong text-info" title="Promote Students">
                        <i class="bi bi-arrow-up-circle"></i>
                      </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-ghost-strong text-danger" onclick="deleteClassroom({{ $classroom->id }})" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                  <form id="delete-form-{{ $classroom->id }}" action="{{ route('academics.classrooms.destroy', $classroom->id) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function deleteClassroom(id) {
    if (confirm('Are you sure you want to delete this classroom? This action cannot be undone.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection

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
        <h1 class="mb-1">Stream Management</h1>
        <p class="text-muted mb-0">Manage streams and map them across classrooms and teachers.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @if(Route::has('students.bulk.assign-streams'))
          <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-ghost-strong">
            <i class="bi bi-people"></i> Bulk Assign Students to Streams
          </a>
        @endif
        <a href="{{ route('academics.streams.create') }}" class="btn btn-settings-primary">
          <i class="bi bi-plus-circle"></i> Add New Stream
        </a>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0"><i class="bi bi-diagram-3"></i> All Streams</h5>
          <p class="text-muted small mb-0">Classroom mapping, teachers, and student counts.</p>
        </div>
        <span class="input-chip">{{ $streams->count() }} total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Stream Name</th>
                <th>Primary Classroom</th>
                <th>Additional Classrooms</th>
                <th>Teachers</th>
                <th>Students</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($streams as $stream)
                <tr>
                  <td class="fw-semibold"><span class="pill-badge pill-info">{{ $stream->name }}</span></td>
                  <td>
                    @if($stream->classroom)
                      <span class="pill-badge pill-primary">{{ $stream->classroom->name }}</span>
                    @else
                      <span class="text-muted">Not set</span>
                    @endif
                  </td>
                  <td>
                    @if($stream->classrooms->count() > 0)
                      <div class="d-flex flex-wrap gap-1">
                        @foreach($stream->classrooms as $classroom)
                          <span class="pill-badge pill-secondary">{{ $classroom->name }}</span>
                        @endforeach
                      </div>
                    @else
                      <span class="text-muted">None</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $streamTeachers = \DB::table('stream_teacher')
                        ->where('stream_id', $stream->id)
                        ->join('users', 'stream_teacher.teacher_id', '=', 'users.id')
                        ->select('users.name')
                        ->distinct()
                        ->get();
                    @endphp
                    @if($streamTeachers->count() > 0)
                      <div class="d-flex flex-wrap gap-1">
                        @foreach($streamTeachers as $teacher)
                          <span class="pill-badge pill-success">{{ $teacher->name }}</span>
                        @endforeach
                      </div>
                    @else
                      <span class="text-muted">No teachers assigned</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $streamStudents = \App\Models\Student::where('stream_id', $stream->id)->where('archive', 0)->count();
                    @endphp
                    <span class="pill-badge pill-info">{{ $streamStudents }} student(s)</span>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      @if(Route::has('students.bulk.assign-streams') && $stream->classroom)
                        <a href="{{ route('students.bulk.assign-streams', ['classroom_id' => $stream->classroom->id]) }}" class="btn btn-sm btn-ghost-strong text-success" title="Assign Students">
                          <i class="bi bi-people"></i>
                        </a>
                      @endif
                      <a href="{{ route('academics.streams.edit', $stream->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-ghost-strong text-danger" onclick="deleteStream({{ $stream->id }})" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    <form id="delete-form-{{ $stream->id }}" action="{{ route('academics.streams.destroy', $stream->id) }}" method="POST" class="d-none">
                      @csrf
                      @method('DELETE')
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No streams found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function deleteStream(id) {
    if (confirm('Are you sure you want to delete this stream? This action cannot be undone.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection

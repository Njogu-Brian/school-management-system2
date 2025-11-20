@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Assign Teachers to Class Streams</h2>
      <small class="text-muted">Assign teachers to specific streams (e.g., Grade 1 Love, Grade 1 Peace)</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Stream Teacher Assignments</h5>
    </div>
    <div class="card-body">
      @forelse($classrooms as $classroom)
        <div class="card mb-3">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-building"></i> {{ $classroom->name }}</h6>
          </div>
          <div class="card-body">
            {{-- Classes without streams can have teachers assigned directly --}}
            @if($classroom->streams->count() > 0)
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Stream</th>
                      <th>Assigned Teacher(s)</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($classroom->streams as $stream)
                      <tr>
                        <td class="fw-semibold">{{ $stream->name }}</td>
                        <td>
                          @php
                            // Get teachers assigned to this stream for this specific classroom
                            $streamTeachers = \DB::table('stream_teacher')
                                ->where('stream_id', $stream->id)
                                ->where('classroom_id', $classroom->id)
                                ->join('users', 'stream_teacher.teacher_id', '=', 'users.id')
                                ->select('users.id', 'users.name')
                                ->get();
                          @endphp
                          @if($streamTeachers->count() > 0)
                            <div class="d-flex flex-wrap gap-2">
                              @foreach($streamTeachers as $teacher)
                                <span class="badge bg-primary">{{ $teacher->name }}</span>
                              @endforeach
                            </div>
                          @else
                            <span class="text-muted">No teacher assigned</span>
                          @endif
                        </td>
                        <td class="text-end">
                          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignStreamModal{{ $stream->id }}">
                            <i class="bi bi-pencil"></i> Assign Teacher
                          </button>
                        </td>
                      </tr>

                      {{-- Assign Modal --}}
                      <div class="modal fade" id="assignStreamModal{{ $stream->id }}" tabindex="-1">
                        <div class="modal-dialog">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Assign Teacher to {{ $classroom->name }} - {{ $stream->name }}</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('academics.streams.assign-teachers', $stream->id) }}" method="POST" id="assignStreamForm{{ $stream->id }}">
                              @csrf
                              <input type="hidden" name="stream_id" value="{{ $stream->id }}">
                              <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                              <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label">Select Teacher for <strong>{{ $stream->name }}</strong> stream in <strong>{{ $classroom->name }}</strong></label>
                                  @php
                                    // Get teachers assigned to this stream for this specific classroom
                                    $assignedTeacherIds = \DB::table('stream_teacher')
                                        ->where('stream_id', $stream->id)
                                        ->where('classroom_id', $classroom->id)
                                        ->pluck('teacher_id')
                                        ->toArray();
                                  @endphp
                                  <select name="teacher_ids[]" class="form-select" multiple size="8" id="teacherSelect{{ $stream->id }}">
                                    @foreach($teachers as $teacher)
                                      <option value="{{ $teacher->id }}" @selected(in_array($teacher->id, $assignedTeacherIds))>
                                        {{ $teacher->name }}
                                      </option>
                                    @endforeach
                                  </select>
                                  <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers (if needed)</small>
                                  <div class="mt-2">
                                    <small class="text-info">
                                      <i class="bi bi-info-circle"></i> Stream ID: {{ $stream->id }} | Classroom ID: {{ $classroom->id }} | Classroom: {{ $classroom->name }}
                                    </small>
                                  </div>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Assignment</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              {{-- Class without streams - assign teachers directly --}}
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h6 class="mb-1">Direct Class Assignment</h6>
                  <small class="text-muted">This class has no streams. Assign teachers directly to the class.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignClassroomModal{{ $classroom->id }}">
                  <i class="bi bi-pencil"></i> Assign Teachers
                </button>
              </div>
              
              @if($classroom->teachers->count() > 0)
                <div class="d-flex flex-wrap gap-2 mb-2">
                  @foreach($classroom->teachers as $teacher)
                    <span class="badge bg-primary">{{ $teacher->name }}</span>
                  @endforeach
                </div>
              @else
                <div class="alert alert-info mb-0">
                  <i class="bi bi-info-circle"></i> No teachers assigned to {{ $classroom->name }}. 
                  Click "Assign Teachers" to add teachers.
                </div>
              @endif

              {{-- Assign Classroom Modal --}}
              <div class="modal fade" id="assignClassroomModal{{ $classroom->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Assign Teachers to {{ $classroom->name }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('academics.classrooms.assign-teachers', $classroom) }}" method="POST">
                      @csrf
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Select Teacher(s)</label>
                          <select name="teacher_ids[]" class="form-select" multiple size="8">
                            @foreach($teachers as $teacher)
                              <option value="{{ $teacher->id }}" @selected($classroom->teachers->contains($teacher->id))>
                                {{ $teacher->name }}
                              </option>
                            @endforeach
                          </select>
                          <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers</small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Assignment</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            @endif
          </div>
        </div>
      @empty
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> No classrooms found. 
          <a href="{{ route('academics.classrooms.index') }}">Create classrooms</a> first.
        </div>
      @endforelse
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Ensure each stream form only submits for its specific stream
    document.querySelectorAll('[id^="assignStreamForm"]').forEach(form => {
      form.addEventListener('submit', function(e) {
        const streamId = this.querySelector('input[name="stream_id"]')?.value;
        const formAction = this.getAttribute('action');
        
        // Verify the stream ID in the form matches the route parameter
        if (streamId && formAction) {
          const routeStreamId = formAction.match(/streams\/(\d+)\/assign-teachers/)?.[1];
          if (routeStreamId && routeStreamId !== streamId) {
            e.preventDefault();
            alert('Error: Stream ID mismatch. Please refresh the page and try again.');
            return false;
          }
        }
        
        // Log for debugging
        console.log('Submitting teacher assignment for stream:', streamId);
      });
    });
  });
</script>
@endpush
@endsection

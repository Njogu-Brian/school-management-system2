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
                          @if($stream->teachers->count() > 0)
                            <div class="d-flex flex-wrap gap-2">
                              @foreach($stream->teachers as $teacher)
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
                            <form action="{{ route('academics.streams.assign-teachers', $stream) }}" method="POST">
                              @csrf
                              <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label">Select Teacher</label>
                                  <select name="teacher_ids[]" class="form-select" multiple size="8">
                                    @foreach($teachers as $teacher)
                                      <option value="{{ $teacher->id }}" @selected($stream->teachers->contains($teacher->id))>
                                        {{ $teacher->name }}
                                      </option>
                                    @endforeach
                                  </select>
                                  <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers (if needed)</small>
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
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Assign Teachers to Classes & Streams</h2>
      <small class="text-muted">Manage teacher assignments for classes and streams</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#classTab" type="button">
        <i class="bi bi-building"></i> Classes
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#streamTab" type="button">
        <i class="bi bi-diagram-3"></i> Streams
      </button>
    </li>
  </ul>

  <div class="tab-content">
    {{-- CLASS ASSIGNMENTS --}}
    <div class="tab-pane fade show active" id="classTab">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-building"></i> Class Teacher Assignments</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Class</th>
                  <th>Assigned Teachers</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($classrooms as $classroom)
                  <tr>
                    <td class="fw-semibold">{{ $classroom->name }}</td>
                    <td>
                      @if($classroom->teachers->count() > 0)
                        <div class="d-flex flex-wrap gap-2">
                          @foreach($classroom->teachers as $teacher)
                            <span class="badge bg-primary">{{ $teacher->name }}</span>
                          @endforeach
                        </div>
                      @else
                        <span class="text-muted">No teachers assigned</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignClassModal{{ $classroom->id }}">
                        <i class="bi bi-pencil"></i> Assign
                      </button>
                    </td>
                  </tr>

                  {{-- Assign Modal --}}
                  <div class="modal fade" id="assignClassModal{{ $classroom->id }}" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Assign Teachers to {{ $classroom->name }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('academics.classrooms.update', $classroom) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <div class="modal-body">
                            <input type="hidden" name="name" value="{{ $classroom->name }}">
                            <div class="mb-3">
                              <label class="form-label">Select Teachers</label>
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
                            <button type="submit" class="btn btn-primary">Save Assignments</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No classrooms found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- STREAM ASSIGNMENTS --}}
    <div class="tab-pane fade" id="streamTab">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Stream Teacher Assignments</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Stream</th>
                  <th>Assigned Teachers</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($streams as $stream)
                  <tr>
                    <td class="fw-semibold">{{ $stream->name }}</td>
                    <td>
                      @if($stream->teachers->count() > 0)
                        <div class="d-flex flex-wrap gap-2">
                          @foreach($stream->teachers as $teacher)
                            <span class="badge bg-success">{{ $teacher->name }}</span>
                          @endforeach
                        </div>
                      @else
                        <span class="text-muted">No teachers assigned</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignStreamModal{{ $stream->id }}">
                        <i class="bi bi-pencil"></i> Assign
                      </button>
                    </td>
                  </tr>

                  {{-- Assign Modal --}}
                  <div class="modal fade" id="assignStreamModal{{ $stream->id }}" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Assign Teachers to {{ $stream->name }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('academics.streams.assign-teachers', $stream) }}" method="POST">
                          @csrf
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Select Teachers</label>
                              <select name="teacher_ids[]" class="form-select" multiple size="8">
                                @foreach($teachers as $teacher)
                                  <option value="{{ $teacher->id }}" @selected($stream->teachers->contains($teacher->id))>
                                    {{ $teacher->name }}
                                  </option>
                                @endforeach
                              </select>
                              <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers</small>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Assignments</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">No streams found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection


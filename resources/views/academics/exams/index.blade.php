@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Exams Management</h2>
      <small class="text-muted">Create, manage, schedule, enter marks, and publish exams</small>
    </div>
    <a href="{{ route('academics.exams.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> New Exam
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Statistics Cards --}}
  <div class="row mb-4">
    <div class="col-md-2">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Total Exams</h6>
              <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
            </div>
            <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-secondary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Draft</h6>
              <h3 class="mb-0">{{ $stats['draft'] ?? 0 }}</h3>
            </div>
            <i class="bi bi-file-earmark fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-info text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Open</h6>
              <h3 class="mb-0">{{ $stats['open'] ?? 0 }}</h3>
            </div>
            <i class="bi bi-unlock fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-warning text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Marking</h6>
              <h3 class="mb-0">{{ $stats['marking'] ?? 0 }}</h3>
            </div>
            <i class="bi bi-pencil-square fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-success text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Approved</h6>
              <h3 class="mb-0">{{ $stats['approved'] ?? 0 }}</h3>
            </div>
            <i class="bi bi-check-circle fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-danger text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Locked</h6>
              <h3 class="mb-0">{{ $stats['locked'] ?? 0 }}</h3>
            </div>
            <i class="bi bi-lock fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Search</label>
          <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Exam name, type...">
        </div>
        <div class="col-md-2">
          <label class="form-label">Type</label>
          <select name="type" class="form-select">
            <option value="">All Types</option>
            @foreach(['cat','midterm','endterm','sba','mock','quiz'] as $t)
              <option value="{{ $t }}" @selected(request('type')==$t)>{{ strtoupper($t) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Academic Year</label>
          <select name="year_id" class="form-select">
            <option value="">All Years</option>
            @foreach($years ?? [] as $y)
              <option value="{{ $y->id }}" @selected(request('year_id')==$y->id)>{{ $y->year }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Term</label>
          <select name="term_id" class="form-select">
            <option value="">All Terms</option>
            @foreach($terms ?? [] as $t)
              <option value="{{ $t->id }}" @selected(request('term_id')==$t->id)>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filter
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Exams</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Exam Name</th>
              <th>Type</th>
              <th>Academic Year / Term</th>
              <th>Class/Subject</th>
              <th>Dates</th>
              <th>Marks</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($exams ?? [] as $exam)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $exam->name }}</div>
                  <div class="small text-muted">
                    <i class="bi bi-{{ $exam->modality === 'online' ? 'laptop' : 'file-earmark' }}"></i>
                    {{ ucfirst($exam->modality) }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-info">{{ strtoupper($exam->type) }}</span>
                </td>
                <td>
                  <div>{{ $exam->academicYear->year ?? '—' }}</div>
                  <div class="small text-muted">{{ $exam->term->name ?? '—' }}</div>
                </td>
                <td>
                  @if($exam->classroom)
                    <div>{{ $exam->classroom->name }}</div>
                  @endif
                  @if($exam->subject)
                    <div class="small text-muted">{{ $exam->subject->name }}</div>
                  @endif
                  @if(!$exam->classroom && !$exam->subject)
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($exam->starts_on && $exam->ends_on)
                    <div class="small">{{ $exam->starts_on->format('M d') }} - {{ $exam->ends_on->format('M d, Y') }}</div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <div class="small">
                    <span class="badge bg-{{ $exam->marks_count > 0 ? 'success' : 'secondary' }}">
                      {{ $exam->marks_count ?? 0 }} entered
                    </span>
                  </div>
                </td>
                <td>
                  <span class="badge bg-{{ $exam->status_badge }}">
                    {{ ucfirst($exam->status) }}
                  </span>
                  <div class="small mt-1">
                    @if($exam->publish_exam)
                      <span class="badge bg-success">Published</span>
                    @endif
                    @if($exam->publish_result)
                      <span class="badge bg-info">Result</span>
                    @endif
                  </div>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="{{ route('academics.exams.show', $exam->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('academics.exams.edit', $exam->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    @if($exam->can_enter_marks)
                      <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" class="btn btn-sm btn-outline-success" title="Enter Marks">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" title="More">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <a class="dropdown-item" href="{{ route('academics.exams.schedules.index', $exam->id) }}">
                          <i class="bi bi-calendar-week"></i> Schedule
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="{{ route('academics.exams.timetable', ['exam_id' => $exam->id]) }}">
                          <i class="bi bi-printer"></i> Timetable
                        </a>
                      </li>
                      @if($exam->can_publish)
                        <li>
                          <form action="{{ route('exams.publish', $exam->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Publish results to report cards?')">
                            @csrf
                            <button type="submit" class="dropdown-item text-success">
                              <i class="bi bi-cloud-upload"></i> Publish Results
                            </button>
                          </form>
                        </li>
                      @endif
                      @if(!$exam->is_locked)
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <form action="{{ route('academics.exams.destroy', $exam->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this exam?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="bi bi-trash"></i> Delete
                            </button>
                          </form>
                        </li>
                      @endif
                    </ul>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No exams found. Create your first exam to get started.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(isset($exams) && $exams->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $exams->firstItem() }}–{{ $exams->lastItem() }} of {{ $exams->total() }} exams
        </div>
        {{ $exams->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

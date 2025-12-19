@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Exams Management</h1>
        <p class="text-muted mb-0">Create, schedule, enter marks, and publish exams.</p>
      </div>
      <a href="{{ route('academics.exams.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> New Exam</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-3">
      <div class="col-md-2"><div class="settings-card stat-card border-start border-4 border-primary"><div class="card-body"><div class="text-muted text-uppercase small">Total Exams</div><h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-2"><div class="settings-card stat-card border-start border-4 border-secondary"><div class="card-body"><div class="text-muted text-uppercase small">Draft</div><h3 class="mb-0">{{ $stats['draft'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-2"><div class="settings-card stat-card border-start border-4 border-info"><div class="card-body"><div class="text-muted text-uppercase small">Open</div><h3 class="mb-0">{{ $stats['open'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-2"><div class="settings-card stat-card border-start border-4 border-warning"><div class="card-body"><div class="text-muted text-uppercase small">Marking</div><h3 class="mb-0">{{ $stats['marking'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-2"><div class="settings-card stat-card border-start border-4 border-success"><div class="card-body"><div class="text-muted text-uppercase small">Approved</div><h3 class="mb-0">{{ $stats['approved'] ?? 0 }}</h3></div></div></div>
      <div class="col-md-2"><div class="settings-card stat-card border-start border-4 border-danger"><div class="card-body"><div class="text-muted text-uppercase small">Locked</div><h3 class="mb-0">{{ $stats['locked'] ?? 0 }}</h3></div></div></div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
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
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i></button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Exams</h5>
          <p class="text-muted small mb-0">Status, dates, marks, and quick actions.</p>
        </div>
        @if(isset($exams))<span class="input-chip">{{ $exams->total() ?? $exams->count() }} total</span>@endif
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
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
                    <div class="small text-muted"><i class="bi bi-{{ $exam->modality === 'online' ? 'laptop' : 'file-earmark' }}"></i> {{ ucfirst($exam->modality) }}</div>
                  </td>
                  <td><span class="pill-badge pill-info">{{ strtoupper($exam->type) }}</span></td>
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
                  <td><span class="pill-badge pill-{{ $exam->marks_count > 0 ? 'success' : 'muted' }}">{{ $exam->marks_count ?? 0 }} entered</span></td>
                  <td>
                    <span class="pill-badge pill-{{ $exam->status_badge }}">{{ ucfirst($exam->status) }}</span>
                    <div class="small mt-1">
                      @if($exam->publish_exam)<span class="pill-badge pill-success">Exam Published</span>@endif
                      @if($exam->publish_result)<span class="pill-badge pill-info">Result</span>@endif
                    </div>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.exams.show', $exam->id) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      <a href="{{ route('academics.exams.edit', $exam->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      @if($exam->can_enter_marks)
                        <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" class="btn btn-sm btn-ghost-strong text-success" title="Enter Marks"><i class="bi bi-pencil-square"></i></a>
                      @endif
                      <div class="dropdown">
                        <button class="btn btn-sm btn-ghost-strong dropdown-toggle" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li><a class="dropdown-item" href="{{ route('academics.exams.schedules.index', $exam->id) }}"><i class="bi bi-calendar-week"></i> Schedule</a></li>
                          <li><a class="dropdown-item" href="{{ route('academics.exams.timetable', ['exam_id' => $exam->id]) }}"><i class="bi bi-printer"></i> Timetable</a></li>
                          @if($exam->can_publish)
                            <li>
                              <form action="{{ route('exams.publish', $exam->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Publish results to report cards?')">
                                @csrf
                                <button type="submit" class="dropdown-item text-success"><i class="bi bi-cloud-upload"></i> Publish Results</button>
                              </form>
                            </li>
                          @endif
                          @if(!$exam->is_locked)
                            <li><hr class="dropdown-divider"></li>
                            <li>
                              <form action="{{ route('academics.exams.destroy', $exam->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this exam?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                              </form>
                            </li>
                          @endif
                        </ul>
                      </div>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No exams found. Create your first exam.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if(isset($exams) && $exams->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div class="small text-muted">Showing {{ $exams->firstItem() }}–{{ $exams->lastItem() }} of {{ $exams->total() }} exams</div>
          {{ $exams->withQueryString()->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

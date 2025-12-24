@extends('layouts.app')

@section('page-title', 'Archived Students')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Archived' => null]])

    {{-- Hero header to match other modules --}}
    <div class="hero-card mb-3">
      <div class="hero-details">
        <div class="hero-info">
          <div class="crumb text-light">Students</div>
          <h1 class="mb-1 text-white">Archived Students</h1>
          <p class="mb-0 text-light">Finance stays intact; restore to reactivate.</p>
        </div>
        <div class="hero-actions d-flex gap-2 flex-wrap">
          <a href="{{ route('students.index') }}" class="btn btn-ghost-light">
            <i class="bi bi-arrow-left"></i> Back to Students
          </a>
          @if(Route::has('students.export'))
          <a href="{{ route('students.export', request()->query()) }}" class="btn btn-ghost-light">
            <i class="bi bi-download"></i> Export CSV
          </a>
          @endif
        </div>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Search by name or admission number.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Search by name">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Admission #</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-card-text"></i></span>
              <input type="text" name="admission_number" value="{{ request('admission_number') }}" class="form-control" placeholder="Admission number">
            </div>
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2 flex-wrap">
            <button type="submit" class="btn btn-settings-primary"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('students.archived') }}" class="btn btn-ghost-strong">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Archived List</h5>
          <p class="text-muted small mb-0">Click a student to view profile.</p>
        </div>
        <span class="pill-badge pill-secondary">{{ $students->total() }} students</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Admission</th>
                <th>Student</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Archived</th>
                <th>Reason</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
            @forelse($students as $s)
              <tr class="list-item-hover">
                <td class="fw-semibold">#{{ $s->admission_number }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar-placeholder avatar-sm">
                      @if($s->photo_path)
                        <img src="{{ asset('storage/' . $s->photo_path) }}" alt="Photo" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                      @else
                        <i class="bi bi-person"></i>
                      @endif
                    </div>
                    <div>
                      <a href="{{ route('students.show', $s->id) }}" class="fw-semibold text-reset d-block">
                        {{ $s->first_name }} {{ $s->last_name }}
                      </a>
                      <div class="text-muted small">
                        <span class="me-2">{{ $s->category->name ?? '—' }}</span>
                        <span class="pill-badge pill-danger pill-sm">Archived</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>{{ $s->classroom->name ?? '—' }}</td>
                <td>{{ $s->stream->name ?? '—' }}</td>
                <td>{{ $s->archived_at?->format('M d, Y H:i') ?? '—' }}</td>
                <td>{{ $s->archived_reason ?? '—' }}</td>
                <td class="text-end">
                  <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('students.show', $s->id) }}" class="btn btn-ghost-strong btn-sm">
                      <i class="bi bi-eye"></i>
                    </a>
                    <form action="{{ route('students.restore', $s->id) }}" method="POST" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5">
                  <div class="empty-state">
                    <i class="bi bi-archive display-4 text-muted mb-3 d-block"></i>
                    <h6 class="fw-semibold mb-1">No archived students</h6>
                    <p class="text-muted small mb-0">Archived students will appear here.</p>
                  </div>
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if($students->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
        </div>
        {{ $students->links() }}
      </div>
      @endif
    </div>
  </div>
</div>
@endsection

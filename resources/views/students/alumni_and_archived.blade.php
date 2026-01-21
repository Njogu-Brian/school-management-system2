@extends('layouts.app')

@section('page-title', 'Alumni & Archived Students')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Alumni & Archived' => null]])

    {{-- Hero header --}}
    <div class="hero-card mb-3">
      <div class="hero-details">
        <div class="hero-info">
          <div class="crumb text-light">Students</div>
          <h1 class="mb-1 text-white">Alumni & Archived Students</h1>
          <p class="mb-0 text-light">View and manage alumni and archived student records with full access to financial, attendance, academic, and behavior data.</p>
        </div>
        <div class="hero-actions d-flex gap-2 flex-wrap">
          <a href="{{ route('students.index') }}" class="btn btn-ghost-light">
            <i class="bi bi-arrow-left"></i> Back to Students
          </a>
          @if(Route::has('students.export'))
          <a href="{{ route('students.export', array_merge(request()->query(), ['showArchived' => 1])) }}" class="btn btn-ghost-light">
            <i class="bi bi-download"></i> Export CSV
          </a>
          @endif
        </div>
      </div>
    </div>

    @include('students.partials.alerts')

    {{-- Filter Tabs --}}
    <div class="settings-card mb-3">
      <ul class="nav nav-tabs settings-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <a class="nav-link {{ $type === 'all' ? 'active' : '' }}" href="{{ route('students.alumni-and-archived', array_merge(request()->except('type'), ['type' => 'all'])) }}">
            <i class="bi bi-collection"></i> All
          </a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link {{ $type === 'alumni' ? 'active' : '' }}" href="{{ route('students.alumni-and-archived', array_merge(request()->except('type'), ['type' => 'alumni'])) }}">
            <i class="bi bi-mortarboard"></i> Alumni
          </a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link {{ $type === 'archived' ? 'active' : '' }}" href="{{ route('students.alumni-and-archived', array_merge(request()->except('type'), ['type' => 'archived'])) }}">
            <i class="bi bi-archive"></i> Archived
          </a>
        </li>
      </ul>
    </div>

    {{-- Filters --}}
    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Search and filter students.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <input type="hidden" name="type" value="{{ $type }}">
          <div class="col-md-3">
            <label class="form-label">Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Search by name">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Admission #</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-card-text"></i></span>
              <input type="text" name="admission_number" value="{{ request('admission_number') }}" class="form-control" placeholder="Admission number">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classes</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stream</label>
            <select name="stream_id" class="form-select">
              <option value="">All Streams</option>
              @foreach($streams as $stream)
                <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 d-flex align-items-end gap-2 flex-wrap">
            <button type="submit" class="btn btn-settings-primary"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('students.alumni-and-archived', ['type' => $type]) }}" class="btn btn-ghost-strong">Clear</a>
          </div>
        </form>
      </div>
    </div>

    {{-- Students Table --}}
    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Students List</h5>
          <p class="text-muted small mb-0">Click on a student to view full details and perform actions.</p>
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
                <th>Status</th>
                <th>Date</th>
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
                        {{ $s->full_name }}
                      </a>
                      <div class="text-muted small">
                        <span class="me-2">{{ $s->category->name ?? '—' }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>{{ $s->classroom->name ?? '—' }}</td>
                <td>{{ $s->stream->name ?? '—' }}</td>
                <td>
                  @if($s->is_alumni)
                    <span class="pill-badge pill-primary pill-sm">
                      <i class="bi bi-mortarboard me-1"></i>Alumni
                    </span>
                  @elseif($s->archive)
                    <span class="pill-badge pill-danger pill-sm">
                      <i class="bi bi-archive me-1"></i>Archived
                    </span>
                  @endif
                </td>
                <td>
                  @if($s->is_alumni && $s->alumni_date)
                    {{ $s->alumni_date->format('M d, Y') }}
                  @elseif($s->archive && $s->archived_at)
                    {{ $s->archived_at->format('M d, Y') }}
                  @else
                    —
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-ghost-strong btn-sm" onclick="viewStudentDetails({{ $s->id }})" title="View Details">
                      <i class="bi bi-eye"></i> View
                    </button>
                    @if($s->archive && !$s->is_alumni)
                      <form action="{{ route('students.restore', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this student?');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" title="Restore Student">
                          <i class="bi bi-arrow-counterclockwise"></i> Restore
                        </button>
                      </form>
                    @endif
                    @if(Route::has('finance.payments.create'))
                      <a href="{{ route('finance.payments.create', ['student_id' => $s->id]) }}" class="btn btn-primary btn-sm" title="Collect Payment">
                        <i class="bi bi-cash-stack"></i> Payment
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5">
                  <div class="empty-state">
                    <i class="bi bi-archive display-4 text-muted mb-3 d-block"></i>
                    <h6 class="fw-semibold mb-1">No {{ $type === 'alumni' ? 'alumni' : ($type === 'archived' ? 'archived' : 'alumni or archived') }} students</h6>
                    <p class="text-muted small mb-0">Students will appear here when they are marked as alumni or archived.</p>
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

{{-- Student Details Modal --}}
<div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="studentDetailsModalLabel">Student Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="studentDetailsContent">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
function viewStudentDetails(studentId) {
  const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
  const content = document.getElementById('studentDetailsContent');
  
  // Show loading
  content.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  `;
  
  modal.show();
  
  // Fetch student details
  fetch(`/students/${studentId}/details-ajax`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        content.innerHTML = data.html;
      } else {
        content.innerHTML = `
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>Error loading student details. Please try again.
          </div>
        `;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      content.innerHTML = `
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>Error loading student details. Please try again.
        </div>
      `;
    });
}

// Handle tab switching in modal
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('studentDetailsModal');
  if (modal) {
    modal.addEventListener('shown.bs.modal', function() {
      // Initialize Bootstrap tabs when modal is shown
      const tabElements = modal.querySelectorAll('[data-bs-toggle="tab"]');
      tabElements.forEach(tab => {
        tab.addEventListener('click', function(e) {
          e.preventDefault();
          const target = this.getAttribute('data-bs-target');
          const tabPane = modal.querySelector(target);
          if (tabPane) {
            // Hide all tab panes
            modal.querySelectorAll('.tab-pane').forEach(pane => {
              pane.classList.remove('show', 'active');
            });
            // Remove active from all nav links
            modal.querySelectorAll('.nav-link').forEach(link => {
              link.classList.remove('active');
            });
            // Show selected tab pane
            tabPane.classList.add('show', 'active');
            this.classList.add('active');
          }
        });
      });
    });
  }
});
</script>
@endpush
@endsection

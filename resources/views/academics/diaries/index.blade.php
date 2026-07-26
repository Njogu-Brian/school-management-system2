@extends('layouts.app')

@push('styles')
    @include('academics.diaries.partials.styles')
@endpush

@section('content')
@php
    $activity = request('activity', 'all');
    $hasFilters = request()->filled('search') || request()->filled('classroom_id') || ($activity !== 'all');
@endphp
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header">
      <div>
        <div class="crumb">Homework &amp; Diaries · Digital Diaries</div>
        <h1 class="mb-1">Digital Diaries</h1>
        <p class="mb-0">Search student threads, filter by class, and post updates.</p>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-settings-primary" data-bs-toggle="modal" data-bs-target="#bulkDiaryModal">
          <i class="bi bi-journal-plus"></i> New Entry
        </button>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if(($orphanedCount ?? 0) > 0 && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
      <div class="alert alert-warning diary-alert-orphans d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <strong>{{ $orphanedCount }} orphaned {{ \Illuminate\Support\Str::plural('diary', $orphanedCount) }}</strong>
          found — student records were deleted but the diary shells remained. They are hidden from this list.
        </div>
        <form method="POST" action="{{ route('academics.diaries.purge-orphans') }}"
              onsubmit="return confirm('Permanently delete {{ $orphanedCount }} orphaned {{ \Illuminate\Support\Str::plural('diary', $orphanedCount) }} and their entries?');">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i> Clean up
          </button>
        </form>
      </div>
    @endif

    <div class="diary-stats">
      <a class="diary-stat {{ $activity === 'all' ? 'is-active' : '' }}"
         href="{{ route('academics.diaries.index', request()->except('activity', 'page')) }}">
        <div class="label">All diaries</div>
        <div class="value">{{ $stats['total'] ?? 0 }}</div>
      </a>
      <a class="diary-stat {{ $activity === 'active' ? 'is-active' : '' }}"
         href="{{ route('academics.diaries.index', array_merge(request()->except('page'), ['activity' => 'active'])) }}">
        <div class="label">With entries</div>
        <div class="value">{{ $stats['active'] ?? 0 }}</div>
      </a>
      <a class="diary-stat {{ $activity === 'empty' ? 'is-active' : '' }}"
         href="{{ route('academics.diaries.index', array_merge(request()->except('page'), ['activity' => 'empty'])) }}">
        <div class="label">No entries yet</div>
        <div class="value">{{ $stats['empty'] ?? 0 }}</div>
      </a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="diary-toolbar" role="search">
          <div>
            <label class="form-label" for="diary-search">Search</label>
            <input id="diary-search" type="search" name="search" class="form-control"
                   value="{{ request('search') }}"
                   placeholder="Name or admission number"
                   autocomplete="off">
          </div>
          <div>
            <label class="form-label" for="diary-classroom">Classroom</label>
            <select id="diary-classroom" name="classroom_id" class="form-select">
              <option value="">All classes</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>
                  {{ $classroom->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="form-label" for="diary-activity">Activity</label>
            <select id="diary-activity" name="activity" class="form-select">
              <option value="all" @selected($activity === 'all')>All</option>
              <option value="active" @selected($activity === 'active')>With entries</option>
              <option value="empty" @selected($activity === 'empty')>Empty</option>
            </select>
          </div>
          <div>
            <button type="submit" class="btn btn-settings-primary w-100">
              <i class="bi bi-funnel"></i> Apply
            </button>
          </div>
          <div>
            @if($hasFilters)
              <a href="{{ route('academics.diaries.index') }}" class="btn btn-ghost-strong w-100">Clear</a>
            @else
              <button type="button" class="btn btn-ghost-strong w-100" disabled>Clear</button>
            @endif
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Student diaries</h5>
          <small class="text-muted">
            Showing {{ $diaries->firstItem() ?? 0 }}–{{ $diaries->lastItem() ?? 0 }} of {{ $diaries->total() }}
          </small>
        </div>
      </div>
      <div class="card-body">
        <div class="diary-board">
          @forelse($diaries as $diary)
            @php
              $student = $diary->student;
              $latest = $diary->latestEntry;
            @endphp
            <article class="diary-row">
              <div>
                <p class="diary-student-name">{{ $student?->name ?? 'Unknown student' }}</p>
                <div class="diary-student-meta">{{ $student?->admission_number ?? '—' }}</div>
              </div>
              <div class="diary-class">
                <span class="diary-class-pill">
                  <i class="bi bi-mortarboard"></i>
                  {{ optional($student?->classroom)->name ?? 'Unassigned' }}
                </span>
              </div>
              <div class="diary-preview">
                @if($latest)
                  <p class="diary-preview-text">{{ \Illuminate\Support\Str::limit($latest->content, 120) }}</p>
                  <div class="diary-preview-meta">
                    {{ $latest->author->name ?? 'Unknown' }} · {{ $latest->created_at->diffForHumans() }}
                  </div>
                @else
                  <p class="diary-preview-text text-muted mb-0">No entries yet</p>
                @endif
              </div>
              <div class="diary-row-actions">
                <span class="diary-updated" title="{{ $diary->updated_at }}">{{ $diary->updated_at->diffForHumans() }}</span>
                <a href="{{ route('academics.diaries.show', $diary) }}" class="btn btn-sm btn-settings-primary">
                  <i class="bi bi-chat-dots"></i> Open
                </a>
              </div>
            </article>
          @empty
            <div class="diary-empty">
              <i class="bi bi-journal-x"></i>
              <div class="fw-semibold text-dark mb-1">No diaries match your filters</div>
              <div>Try another search, class, or clear filters.</div>
            </div>
          @endforelse
        </div>

        @if($diaries->hasPages())
          <div class="d-flex justify-content-center justify-content-md-end mt-3">
            {{ $diaries->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Bulk Entry Modal --}}
<div class="modal fade" id="bulkDiaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="{{ route('academics.diaries.entries.bulk-store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Create Diary Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="target_scope">Target</label>
            <select name="target_scope" id="target_scope" class="form-select" required>
              <option value="student">Specific Student</option>
              <option value="classroom">Entire Classroom</option>
              @if(auth()->user()->hasAnyRole(['Super Admin','Admin']))
                <option value="school">Entire School</option>
              @endif
            </select>
          </div>
          <div class="col-md-4 target-field target-student">
            <label class="form-label" for="bulk_student_id">Student</label>
            <select name="student_id" id="bulk_student_id" class="form-select">
              <option value="">Select Student</option>
              @foreach($students as $studentOption)
                <option value="{{ $studentOption->id }}">{{ $studentOption->name }} ({{ $studentOption->classroom->name ?? '—' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 target-field target-classroom d-none">
            <label class="form-label" for="bulk_classroom_id">Classroom</label>
            <select name="classroom_id" id="bulk_classroom_id" class="form-select">
              <option value="">Select Class</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label" for="bulk_content">Entry</label>
          <textarea name="content" id="bulk_content" rows="4" class="form-control" required placeholder="Write the update, reminder, or feedback..."></textarea>
        </div>
        <div class="mt-3">
          <label class="form-label" for="bulk_attachments">Attachments</label>
          <input type="file" name="attachments[]" id="bulk_attachments" class="form-control" multiple>
          <small class="text-muted">Optional. Max 10MB per file.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-settings-primary">Send Entry</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const targetSelect = document.getElementById('target_scope');
  const targetFields = document.querySelectorAll('.target-field');
  function toggleTargetFields() {
    targetFields.forEach(field => field.classList.add('d-none'));
    document.querySelectorAll('.target-' + targetSelect.value).forEach(field => field.classList.remove('d-none'));
  }
  targetSelect?.addEventListener('change', toggleTargetFields);
  toggleTargetFields();
});
</script>
@endpush
@endsection

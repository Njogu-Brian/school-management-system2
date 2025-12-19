@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Digital Diaries</div>
        <h1 class="mb-1">Digital Diaries</h1>
        <p class="text-muted mb-0">View and post diary entries for every student.</p>
      </div>
      <button class="btn btn-settings-primary" data-bs-toggle="modal" data-bs-target="#bulkDiaryModal"><i class="bi bi-journal-plus"></i> New Entry</button>
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Search Student</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or admission number">
          </div>
          <div class="col-md-3">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classes</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-settings-primary w-100">Filter</button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Student</th><th>Classroom</th><th>Last Entry</th><th>Updated</th><th class="text-end"></th></tr></thead>
            <tbody>
              @forelse($diaries as $diary)
              <tr>
                <td>
                  @if($diary->student)
                    <div class="fw-semibold">{{ $diary->student->getNameAttribute() }}</div>
                    <small class="text-muted">{{ $diary->student->admission_number }}</small>
                  @else
                    <div class="fw-semibold text-muted">Orphaned Diary #{{ $diary->id }}</div>
                    <small class="text-muted">Student record unavailable</small>
                  @endif
                </td>
                <td>{{ optional($diary->student?->classroom)->name ?? '—' }}</td>
                <td>
                  @if($diary->latestEntry)
                    <div class="text-truncate" style="max-width: 280px;">{{ \Illuminate\Support\Str::limit($diary->latestEntry->content, 80) }}</div>
                    <small class="text-muted">by {{ $diary->latestEntry->author->name }} • {{ $diary->latestEntry->created_at->diffForHumans() }}</small>
                  @else
                    <span class="text-muted">No entries yet</span>
                  @endif
                </td>
                <td>{{ $diary->updated_at->diffForHumans() }}</td>
                <td class="text-end"><a href="{{ route('academics.diaries.show', $diary) }}" class="btn btn-sm btn-ghost-strong text-info"><i class="bi bi-chat-dots"></i> Open</a></td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center py-4 text-muted">No diaries found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="p-3 d-flex justify-content-end">{{ $diaries->links() }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Entry Modal -->
<div class="modal fade" id="bulkDiaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('academics.diaries.entries.bulk-store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Create Diary Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Target</label>
            <select name="target_scope" id="target_scope" class="form-select" required>
              <option value="student">Specific Student</option>
              <option value="classroom">Entire Classroom</option>
              @if(auth()->user()->hasAnyRole(['Super Admin','Admin']))
                <option value="school">Entire School</option>
              @endif
            </select>
          </div>
          <div class="col-md-4 target-field target-student">
            <label class="form-label">Student</label>
            <select name="student_id" class="form-select">
              <option value="">Select Student</option>
              @foreach($students as $studentOption)
                <option value="{{ $studentOption->id }}">{{ $studentOption->getNameAttribute() }} ({{ $studentOption->classroom->name ?? '—' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 target-field target-classroom d-none">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select">
              <option value="">Select Class</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Entry</label>
          <textarea name="content" rows="4" class="form-control" required placeholder="Write the update, reminder, or feedback..."></textarea>
        </div>
        <div class="mt-3">
          <label class="form-label">Attachments</label>
          <input type="file" name="attachments[]" class="form-control" multiple>
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
  const targetSelect=document.getElementById('target_scope');
  const targetFields=document.querySelectorAll('.target-field');
  function toggleTargetFields(){
    targetFields.forEach(field=>field.classList.add('d-none'));
    document.querySelectorAll('.target-'+targetSelect.value).forEach(field=>field.classList.remove('d-none'));
  }
  targetSelect?.addEventListener('change', toggleTargetFields);
  toggleTargetFields();
});
</script>
@endpush
@endsection

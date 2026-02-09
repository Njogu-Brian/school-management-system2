@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Families</div>
        <h1 class="mb-1">Families (Siblings)</h1>
        <p class="text-muted mb-0">Manage sibling links and guardian details for billing/discounts.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <form action="{{ route('families.populate') }}" method="POST" class="d-inline" onsubmit="return confirm('This will auto-populate all families with blank fields from their students\\' parent records. Continue?')">
          @csrf
          <button type="submit" class="btn btn-ghost-strong btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Fix Blank Fields
          </button>
        </form>
        <a href="{{ route('families.link') }}" class="btn btn-settings-primary">
          <i class="bi bi-link-45deg"></i> Link Two Students
        </a>
      </div>
    </div>

    <div class="alert alert-soft border-0 mb-3">
      <i class="bi bi-info-circle"></i> Use "Link Two Students" to create families; guardian details come from student parent records. Families enable family-level billing and sibling discounts.
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Search by guardian name, phone, or email.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('families.index') }}" class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Search</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="Guardian name, phone, or email">
            </div>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-funnel"></i> Search</button>
          </div>
          @if($q)
          <div class="col-md-2">
            <a href="{{ route('families.index') }}" class="btn btn-ghost-strong w-100"><i class="bi bi-x"></i> Clear</a>
          </div>
          @endif
        </form>
      </div>
    </div>

    <div class="settings-card">
      <form method="POST" action="{{ route('families.bulk-destroy') }}" id="families-bulk-form" onsubmit="return confirm('Delete the selected families? All students will be unlinked. This cannot be undone.');">
        @csrf
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 2.5rem;">
                    <input type="checkbox" id="select-all-families" class="form-check-input" aria-label="Select all on page">
                  </th>
                  <th>#</th>
                  <th>Guardian</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Students</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($families as $fam)
                  <tr>
                    <td>
                      <input type="checkbox" name="ids[]" value="{{ $fam->id }}" class="form-check-input family-checkbox">
                    </td>
                    <td class="fw-semibold">#{{ $fam->id }}</td>
                  <td>
                    <div class="fw-semibold">{{ $fam->guardian_name }}</div>
                    @if($fam->phone || $fam->email)
                      <div class="text-muted small">
                        @if($fam->phone) <i class="bi bi-telephone"></i> {{ $fam->phone }} @endif
                        @if($fam->phone && $fam->email) · @endif
                        @if($fam->email) <i class="bi bi-envelope"></i> {{ $fam->email }} @endif
                      </div>
                    @endif
                  </td>
                  <td>{{ $fam->phone ?? '—' }}</td>
                  <td>{{ $fam->email ?? '—' }}</td>
                  <td>
                    @if($fam->students->count() > 0)
                      <div class="vstack gap-1">
                        @foreach($fam->students as $student)
                          <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('students.show', $student->id) }}" class="fw-semibold text-reset text-decoration-none">
                              {{ $student->full_name }}
                            </a>
                            @if($student->admission_number)
                              <small class="text-muted">({{ $student->admission_number }})</small>
                            @endif
                          </div>
                        @endforeach
                      </div>
                      <small class="text-muted mt-1 d-block">
                        <span class="pill-badge pill-secondary">{{ $fam->students_count }} {{ Str::plural('sibling', $fam->students_count) }}</span>
                      </small>
                    @else
                      <span class="text-muted">No students</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-ghost-strong" href="{{ route('families.manage', $fam) }}">
                      <i class="bi bi-people"></i> Manage Siblings
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No families found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <button type="submit" class="btn btn-outline-danger btn-sm" id="bulk-delete-btn" disabled>
            <i class="bi bi-trash"></i> Delete selected
          </button>
          {{ $families->links() }}
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('families-bulk-form');
  var selectAll = document.getElementById('select-all-families');
  var checkboxes = form ? form.querySelectorAll('.family-checkbox') : [];
  var bulkBtn = document.getElementById('bulk-delete-btn');

  function updateBulkButton() {
    var any = Array.prototype.some.call(checkboxes, function (c) { return c.checked; });
    if (bulkBtn) bulkBtn.disabled = !any;
  }

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checkboxes.forEach(function (c) { c.checked = selectAll.checked; });
      updateBulkButton();
    });
  }
  checkboxes.forEach(function (c) {
    c.addEventListener('change', updateBulkButton);
  });
  updateBulkButton();
})();
</script>
@endpush

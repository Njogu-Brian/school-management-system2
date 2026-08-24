@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">HR / Staff</div>
        <h1 class="mb-1">Staff registrations</h1>
        <p class="text-muted mb-0">Applications from the public staff registration link.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-ghost-strong" id="copyStaffRegisterLink" data-url="{{ $publicUrl }}">
          <i class="bi bi-link-45deg"></i> Copy public link
        </button>
        <a href="{{ $publicUrl }}" class="btn btn-ghost-strong" target="_blank" rel="noopener">
          <i class="bi bi-globe"></i> Open form
        </a>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All</option>
              @foreach (['pending','approved','rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-settings-primary btn-sm">Filter</button>
            <a href="{{ route('staff.registrations.index') }}" class="btn btn-ghost-strong btn-sm">Clear</a>
          </div>
          <div class="col-md-4 text-md-end">
            <span class="pill-badge pill-secondary">{{ $pendingCount }} pending</span>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="table-responsive">
        <table class="table table-modern mb-0">
          <thead>
            <tr>
              <th>Submitted</th>
              <th>Name</th>
              <th>ID</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($registrations as $row)
              <tr>
                <td>{{ $row->created_at?->format('d M Y H:i') }}</td>
                <td class="fw-semibold">{{ $row->full_name }}</td>
                <td>{{ $row->id_number }}</td>
                <td>{{ $row->phone_number }}</td>
                <td>{{ $row->personal_email }}</td>
                <td>
                  @php
                    $badge = match($row->status) {
                      'approved' => 'success',
                      'rejected' => 'danger',
                      default => 'warning',
                    };
                  @endphp
                  <span class="badge bg-{{ $badge }}">{{ ucfirst($row->status) }}</span>
                </td>
                <td class="text-end">
                  <a href="{{ route('staff.registrations.show', $row) }}" class="btn btn-sm btn-ghost-strong">Review</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-muted text-center py-4">No registrations yet. Share the public link with new staff.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-body">{{ $registrations->links() }}</div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.getElementById('copyStaffRegisterLink')?.addEventListener('click', async function () {
    const url = this.getAttribute('data-url');
    try {
      await navigator.clipboard.writeText(url);
      this.innerHTML = '<i class="bi bi-check2"></i> Copied';
    } catch (e) {
      window.prompt('Copy this link', url);
    }
  });
</script>
@endpush

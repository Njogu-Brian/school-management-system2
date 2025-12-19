@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Online Admissions</div>
        <h1 class="mb-1">Online Admissions</h1>
        <p class="text-muted mb-0">Manage student admission applications.</p>
      </div>
      <a href="{{ route('online-admissions.public-form') }}" class="btn btn-ghost-strong" target="_blank">
        <i class="bi bi-globe"></i> View Public Form
      </a>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Status and waitlist toggle.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Application Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All Statuses</option>
              @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(request('status')==$status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Waiting List Only</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="waitlist_only" value="1" id="waitlist_only" @checked(request('waitlist_only'))>
              <label class="form-check-label" for="waitlist_only">Show Waitlist</label>
            </div>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-settings-primary btn-sm w-100">
              <i class="bi bi-funnel"></i> Filter
            </button>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <a href="{{ route('online-admissions.index') }}" class="btn btn-ghost-strong btn-sm w-100">
              <i class="bi bi-x-circle"></i> Clear
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-primary h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Pending</div>
            <h4 class="mb-0">{{ $admissions->where('application_status', 'pending')->count() }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-info h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Under Review</div>
            <h4 class="mb-0">{{ $admissions->where('application_status', 'under_review')->count() }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-warning h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Waitlisted</div>
            <h4 class="mb-0">{{ $admissions->where('application_status', 'waitlisted')->count() }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card stat-card border-start border-4 border-success h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Accepted</div>
            <h4 class="mb-0">{{ $admissions->where('application_status', 'accepted')->count() }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Ref #</th>
                <th>Student Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Application Date</th>
                <th>Status</th>
                <th>Waitlist Position</th>
                <th>Classroom</th>
                <th>Reviewed By</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($admissions as $admission)
                @php
                  $statusColors = [
                    'pending' => 'pill-secondary',
                    'under_review' => 'pill-info',
                    'accepted' => 'pill-success',
                    'rejected' => 'pill-danger',
                    'waitlisted' => 'pill-warning'
                  ];
                  $color = $statusColors[$admission->application_status] ?? 'pill-secondary';
                @endphp
                <tr>
                  <td class="fw-semibold">#{{ $admission->id }}</td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ $admission->first_name }} {{ $admission->middle_name }} {{ $admission->last_name }}</span>
                      @if($admission->application_source)
                        <small class="text-muted">{{ ucfirst($admission->application_source) }}</small>
                      @endif
                      @if($admission->enrolled)
                        <span class="pill-badge pill-success mt-1">Enrolled</span>
                      @endif
                    </div>
                  </td>
                  <td>{{ $admission->dob?->format('d M Y') ?? '—' }}</td>
                  <td>{{ $admission->gender }}</td>
                  <td>
                    <div>{{ $admission->application_date?->format('d M Y') ?? '—' }}</div>
                    @if($admission->review_date)
                      <small class="text-muted">Reviewed: {{ $admission->review_date->format('d M Y') }}</small>
                    @endif
                  </td>
                  <td><span class="pill-badge {{ $color }}">{{ ucfirst(str_replace('_', ' ', $admission->application_status)) }}</span></td>
                  <td>
                    @if($admission->waitlist_position)
                      <span class="pill-badge pill-warning">#{{ $admission->waitlist_position }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @if($admission->classroom)
                      {{ $admission->classroom->name }} @if($admission->stream)- {{ $admission->stream->name }}@endif
                    @else
                      <span class="text-muted">Not assigned</span>
                    @endif
                  </td>
                  <td>{{ $admission->reviewedBy->name ?? '—' }}</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                      <a href="{{ route('online-admissions.show', $admission) }}" class="btn btn-sm btn-ghost-strong" title="View Details">
                        <i class="bi bi-eye"></i>
                      </a>
                      @if(!$admission->enrolled)
                        @if($admission->application_status === 'waitlisted')
                          <form action="{{ route('online-admissions.transfer', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Transfer this student from waiting list to admitted?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-ghost-strong text-success" title="Transfer from Waitlist">
                              <i class="bi bi-arrow-up-circle"></i>
                            </button>
                          </form>
                        @else
                          <form action="{{ route('online-admissions.approve', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve and enroll this student?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-ghost-strong text-success" title="Approve">
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </form>
                        @endif
                        <form action="{{ route('online-admissions.reject', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this application?')">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Reject">
                            <i class="bi bi-x-circle"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mb-0 mt-2">No applications found</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @if(method_exists($admissions, 'links'))
        <div class="card-footer d-flex justify-content-end">
          {{ $admissions->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

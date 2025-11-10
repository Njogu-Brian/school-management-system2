@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Online Admissions</h2>
      <small class="text-muted">Manage student admission applications</small>
    </div>
    <a href="{{ route('online-admissions.public-form') }}" class="btn btn-outline-primary" target="_blank">
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

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2">
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
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-funnel"></i> Filter
          </button>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <a href="{{ route('online-admissions.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="bi bi-x-circle"></i> Clear
          </a>
        </div>
      </form>
    </div>
  </div>

  {{-- Statistics Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-primary">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-muted d-block">Pending</small>
              <h4 class="mb-0">{{ $admissions->where('application_status', 'pending')->count() }}</h4>
            </div>
            <i class="bi bi-clock-history text-primary" style="font-size: 2rem;"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-info">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-muted d-block">Under Review</small>
              <h4 class="mb-0">{{ $admissions->where('application_status', 'under_review')->count() }}</h4>
            </div>
            <i class="bi bi-eye text-info" style="font-size: 2rem;"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-warning">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-muted d-block">Waitlisted</small>
              <h4 class="mb-0">{{ $admissions->where('application_status', 'waitlisted')->count() }}</h4>
            </div>
            <i class="bi bi-list-ol text-warning" style="font-size: 2rem;"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-success">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="text-muted d-block">Accepted</small>
              <h4 class="mb-0">{{ $admissions->where('application_status', 'accepted')->count() }}</h4>
            </div>
            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Applications Table --}}
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
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
              <tr>
                <td class="fw-semibold">#{{ $admission->id }}</td>
                <td>
                  <div>
                    <strong>{{ $admission->first_name }} {{ $admission->middle_name }} {{ $admission->last_name }}</strong>
                    @if($admission->enrolled)
                      <span class="badge bg-success ms-2">Enrolled</span>
                    @endif
                  </div>
                  @if($admission->application_source)
                    <small class="text-muted">{{ ucfirst($admission->application_source) }}</small>
                  @endif
                </td>
                <td>{{ $admission->dob?->format('d M Y') ?? '—' }}</td>
                <td>{{ $admission->gender }}</td>
                <td>
                  <div>{{ $admission->application_date?->format('d M Y') ?? '—' }}</div>
                  @if($admission->review_date)
                    <small class="text-muted">Reviewed: {{ $admission->review_date->format('d M Y') }}</small>
                  @endif
                </td>
                <td>
                  @php
                    $statusColors = [
                      'pending' => 'secondary',
                      'under_review' => 'info',
                      'accepted' => 'success',
                      'rejected' => 'danger',
                      'waitlisted' => 'warning'
                    ];
                    $color = $statusColors[$admission->application_status] ?? 'secondary';
                  @endphp
                  <span class="badge bg-{{ $color }}">
                    {{ ucfirst(str_replace('_', ' ', $admission->application_status)) }}
                  </span>
                </td>
                <td>
                  @if($admission->waitlist_position)
                    <span class="badge bg-warning">#{{ $admission->waitlist_position }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($admission->classroom)
                    {{ $admission->classroom->name }}
                    @if($admission->stream)
                      - {{ $admission->stream->name }}
                    @endif
                  @else
                    <span class="text-muted">Not assigned</span>
                  @endif
                </td>
                <td>
                  @if($admission->reviewedBy)
                    {{ $admission->reviewedBy->name }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('online-admissions.show', $admission) }}" class="btn btn-outline-primary" title="View Details">
                      <i class="bi bi-eye"></i>
                    </a>
                    @if(!$admission->enrolled)
                      @if($admission->application_status === 'waitlisted')
                        <form action="{{ route('online-admissions.transfer', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Transfer this student from waiting list to admitted?')">
                          @csrf
                          <button type="submit" class="btn btn-outline-success" title="Transfer from Waitlist">
                            <i class="bi bi-arrow-up-circle"></i>
                          </button>
                        </form>
                      @else
                        <form action="{{ route('online-admissions.approve', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve and enroll this student?')">
                          @csrf
                          <button type="submit" class="btn btn-outline-success" title="Approve">
                            <i class="bi bi-check-circle"></i>
                          </button>
                        </form>
                      @endif
                      <form action="{{ route('online-admissions.reject', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this application?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger" title="Reject">
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

      {{-- Pagination --}}
      @if(method_exists($admissions, 'links'))
        <div class="mt-3">
          {{ $admissions->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

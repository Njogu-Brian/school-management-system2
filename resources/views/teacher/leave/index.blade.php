@extends('layouts.app')

@php
  $routePrefix = request()->routeIs('senior_teacher.*') ? 'senior_teacher.leave' : 'teacher.leave';
@endphp

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">My Leaves</h2>
      <small class="text-muted">Request leaves and view your leave history</small>
    </div>
    <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Request Leave
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

  <div class="row g-3 mb-4">
    {{-- Leave Balances --}}
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Available Leave Balance</h5>
        </div>
        <div class="card-body">
          <div class="row">
            @forelse($leaveBalances as $balance)
              <div class="col-md-3 mb-3">
                <div class="border rounded p-3">
                  <h6 class="text-muted small mb-2">{{ $balance->leaveType->name }}</h6>
                  <div class="h4 mb-1 text-primary">{{ $balance->remaining_days }}</div>
                  <small class="text-muted">Remaining / {{ $balance->entitlement_days }} days</small>
                </div>
              </div>
            @empty
              <div class="col-12">
                <p class="text-muted mb-0">No leave balances available.</p>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    {{-- Leave Requests --}}
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-list-ul"></i> Leave Requests ({{ $leaveRequests->total() }})</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Leave Type</th>
                  <th>Date Range</th>
                  <th>Days</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($leaveRequests as $request)
                  <tr>
                    <td>
                      <span class="badge bg-info">{{ $request->leaveType->name }}</span>
                    </td>
                    <td>
                      <div>{{ $request->start_date->format('d M Y') }}</div>
                      <small class="text-muted">to {{ $request->end_date->format('d M Y') }}</small>
                    </td>
                    <td>
                      <span class="badge bg-primary">{{ $request->days_requested }} days</span>
                    </td>
                    <td>
                      @php
                        $statusColors = [
                          'pending' => 'warning',
                          'approved' => 'success',
                          'rejected' => 'danger',
                          'cancelled' => 'secondary'
                        ];
                      @endphp
                      <span class="badge bg-{{ $statusColors[$request->status] ?? 'secondary' }}">
                        {{ ucfirst($request->status) }}
                      </span>
                    </td>
                    <td>
                      <small>{{ $request->created_at->format('d M Y, H:i') }}</small>
                    </td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <a href="{{ route($routePrefix . '.show', $request->id) }}" class="btn btn-sm btn-outline-info" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                        @if($request->status === 'pending')
                          <form action="{{ route($routePrefix . '.cancel', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this leave request?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel">
                              <i class="bi bi-x-circle"></i>
                            </button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No leave requests found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if($leaveRequests->hasPages())
          <div class="card-footer">
            {{ $leaveRequests->withQueryString()->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection


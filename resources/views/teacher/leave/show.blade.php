@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Leave Request Details</h2>
      <small class="text-muted">View leave request information</small>
    </div>
    <a href="{{ route('teacher.leave.index') }}" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Request Information</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Leave Type</label>
              <div><span class="badge bg-info">{{ $leaveRequest->leaveType->name }}</span></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Status</label>
              <div>
                @php
                  $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'cancelled' => 'secondary'
                  ];
                @endphp
                <span class="badge bg-{{ $statusColors[$leaveRequest->status] ?? 'secondary' }} fs-6">
                  {{ ucfirst($leaveRequest->status) }}
                </span>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Start Date</label>
              <div class="fw-semibold">{{ $leaveRequest->start_date->format('d M Y') }}</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">End Date</label>
              <div class="fw-semibold">{{ $leaveRequest->end_date->format('d M Y') }}</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Days Requested</label>
              <div><span class="badge bg-primary fs-6">{{ $leaveRequest->days_requested }} days</span></div>
            </div>
            @if($leaveRequest->reason)
            <div class="col-md-12 mb-3">
              <label class="text-muted small">Reason</label>
              <div>{{ $leaveRequest->reason }}</div>
            </div>
            @endif
            @if($leaveRequest->approvedBy)
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Approved By</label>
              <div>{{ $leaveRequest->approvedBy->name }}</div>
              <small class="text-muted">{{ $leaveRequest->approved_at->format('d M Y, H:i') }}</small>
            </div>
            @endif
            @if($leaveRequest->rejectedBy)
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Rejected By</label>
              <div>{{ $leaveRequest->rejectedBy->name }}</div>
              <small class="text-muted">{{ $leaveRequest->rejected_at->format('d M Y, H:i') }}</small>
            </div>
            @if($leaveRequest->rejection_reason)
            <div class="col-md-12 mb-3">
              <label class="text-muted small">Rejection Reason</label>
              <div class="text-danger">{{ $leaveRequest->rejection_reason }}</div>
            </div>
            @endif
            @endif
            @if($leaveRequest->admin_notes)
            <div class="col-md-12 mb-3">
              <label class="text-muted small">Admin Notes</label>
              <div>{{ $leaveRequest->admin_notes }}</div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      @if($leaveRequest->status === 'pending')
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Actions</h5>
        </div>
        <div class="card-body">
          <form action="{{ route('teacher.leave.cancel', $leaveRequest->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this leave request?')">
            @csrf
            <button type="submit" class="btn btn-danger w-100">
              <i class="bi bi-x-circle"></i> Cancel Request
            </button>
          </form>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection


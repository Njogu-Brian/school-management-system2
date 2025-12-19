@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Leave Request Details</h1>
                <p class="text-muted mb-0">Review and act on this leave request.</p>
            </div>
            <a href="{{ route('staff.leave-requests.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Request Information</h5>
                            <p class="text-muted small mb-0">Staff, type, period, and notes.</p>
                        </div>
                        <span class="pill-badge pill-secondary">Ref #{{ $leaveRequest->id }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Staff Member</label>
                                <div class="fw-semibold">{{ $leaveRequest->staff->full_name }}</div>
                                <small class="text-muted">{{ $leaveRequest->staff->staff_id }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Leave Type</label>
                                <div><span class="pill-badge pill-info">{{ $leaveRequest->leaveType->name }}</span></div>
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
                                <div><span class="pill-badge pill-primary fs-6">{{ $leaveRequest->days_requested }} days</span></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Status</label>
                                @php
                                    $statusColors = [
                                        'pending' => 'pill-warning',
                                        'approved' => 'pill-success',
                                        'rejected' => 'pill-danger',
                                        'cancelled' => 'pill-secondary'
                                    ];
                                @endphp
                                <div>
                                    <span class="pill-badge {{ $statusColors[$leaveRequest->status] ?? 'pill-secondary' }} fs-6">
                                        {{ ucfirst($leaveRequest->status) }}
                                    </span>
                                </div>
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
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Actions</h5>
                        <span class="pill-badge pill-warning">Pending</span>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success w-100 mb-2" onclick="approveRequest({{ $leaveRequest->id }})">
                            <i class="bi bi-check-circle"></i> Approve Request
                        </button>
                        <button type="button" class="btn btn-danger w-100" onclick="rejectRequest({{ $leaveRequest->id }})">
                            <i class="bi bi-x-circle"></i> Reject Request
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this leave request?</p>
                    <div class="mb-3">
                        <label class="form-label">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this leave request?</p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveRequest(id) {
    document.getElementById('approveForm').action = `/staff/leave-requests/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectRequest(id) {
    document.getElementById('rejectForm').action = `/staff/leave-requests/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endsection


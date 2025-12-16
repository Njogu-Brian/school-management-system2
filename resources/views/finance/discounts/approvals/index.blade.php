@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">
                        <i class="bi bi-check-circle"></i> Discount Approvals
                    </h3>
                    <p class="text-muted mb-0">Review and approve pending discount allocations</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-outline-success">
                        <i class="bi bi-list-check"></i> Allocations
                    </a>
                    <a href="{{ route('finance.discounts.templates.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-text"></i> Templates
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Pending Approvals</h6>
                            <h3 class="mb-0 text-warning">{{ $pendingApprovals->total() }}</h3>
                        </div>
                        <div class="text-warning" style="font-size: 2.5rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Ready to Approve</h6>
                            <h3 class="mb-0 text-info">{{ $pendingApprovals->where('is_active', true)->count() }}</h3>
                        </div>
                        <div class="text-info" style="font-size: 2.5rem;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('finance.discounts.approvals.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <a href="{{ route('finance.discounts.approvals.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Form -->
    <form id="bulkActionsForm" method="POST" action="">
        @csrf
        <div id="bulkRejectReason" class="card shadow-sm mb-3" style="display: none;">
            <div class="card-body">
                <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" class="form-control" rows="2" required></textarea>
            </div>
        </div>

        <!-- Pending Approvals Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table"></i> Pending Approvals</h5>
                <div id="bulkActions" style="display: none;">
                    <div class="btn-group">
                        <button type="submit" formaction="{{ route('finance.discounts.allocations.bulk-approve') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-check-circle"></i> Approve Selected
                        </button>
                        <button type="button" id="bulkRejectBtn" class="btn btn-sm btn-danger">
                            <i class="bi bi-x-circle"></i> Reject Selected
                        </button>
                        <button type="button" id="clearSelection" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" title="Select All">
                                </th>
                                <th>Student</th>
                                <th>Template</th>
                                <th>Votehead</th>
                                <th>Term/Year</th>
                                <th class="text-end">Value</th>
                                <th>Reason</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingApprovals as $approval)
                            <tr>
                                <td>
                                    <input type="checkbox" name="allocation_ids[]" value="{{ $approval->id }}" class="approval-checkbox">
                                </td>
                                <td>
                                    @if($approval->student)
                                        <strong>{{ $approval->student->first_name }} {{ $approval->student->last_name }}</strong>
                                        <br><small class="text-muted">{{ $approval->student->admission_number }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($approval->discountTemplate)
                                        <span class="badge bg-info">{{ $approval->discountTemplate->name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($approval->votehead)
                                        <span class="badge bg-secondary">{{ $approval->votehead->name }}</span>
                                    @else
                                        <span class="text-muted">All</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>Term {{ $approval->term }}</strong> / {{ $approval->year }}
                                </td>
                                <td class="text-end">
                                    @if($approval->type === 'percentage')
                                        <strong class="text-primary">{{ number_format($approval->value, 1) }}%</strong>
                                    @else
                                        <strong class="text-primary">Ksh {{ number_format($approval->value, 2) }}</strong>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ \Illuminate\Support\Str::limit($approval->reason, 50) }}</small>
                                </td>
                                <td>
                                    {{ $approval->created_at->format('d M Y') }}
                                    @if($approval->creator)
                                        <br><small class="text-muted">by {{ $approval->creator->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <form action="{{ route('finance.discounts.approve', $approval) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $approval->id }}" title="Reject">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $approval->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Discount Allocation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('finance.discounts.reject', $approval) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Enter reason for rejection..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="py-5">
                                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3 mb-0">No pending approvals.</p>
                                        <p class="text-muted">All discounts have been reviewed.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($pendingApprovals->hasPages())
            <div class="card-footer">
                {{ $pendingApprovals->links() }}
            </div>
            @endif
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.approval-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const bulkRejectReason = document.getElementById('bulkRejectReason');
    const bulkActionsForm = document.getElementById('bulkActionsForm');
    const clearSelection = document.getElementById('clearSelection');

    // Select all functionality
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    // Individual checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            selectAll.checked = Array.from(checkboxes).every(c => c.checked);
            updateBulkActions();
        });
    });

    // Update bulk actions visibility
    function updateBulkActions() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        if (checked.length > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
            bulkRejectReason.style.display = 'none';
        }
    }

    // Bulk reject button
    bulkRejectBtn.addEventListener('click', function() {
        if (bulkRejectReason.style.display === 'none') {
            bulkRejectReason.style.display = 'block';
            bulkRejectReason.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            // Submit reject form
            bulkActionsForm.action = '{{ route('finance.discounts.allocations.bulk-reject') }}';
            if (bulkActionsForm.querySelector('textarea[name="rejection_reason"]').value.trim()) {
                bulkActionsForm.submit();
            } else {
                alert('Please provide a rejection reason.');
            }
        }
    });

    // Clear selection
    clearSelection.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        selectAll.checked = false;
        updateBulkActions();
    });
});
</script>
@endpush
@endsection

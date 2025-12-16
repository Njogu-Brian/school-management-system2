@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">
                        <i class="bi bi-list-check"></i> Allocated Discounts
                    </h3>
                    <p class="text-muted mb-0">Manage and approve discount allocations</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Allocate Discount
                    </a>
                    <a href="{{ route('finance.discounts.templates.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-text"></i> Templates
                    </a>
                    <a href="{{ route('finance.discounts.approvals.index') }}" class="btn btn-outline-warning">
                        <i class="bi bi-check-circle"></i> Approvals
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Total Allocations</h6>
                            <h3 class="mb-0">{{ $allocations->total() }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">
                            <i class="bi bi-list-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Approved</h6>
                            <h3 class="mb-0 text-success">{{ $allocations->where('approval_status', 'approved')->count() }}</h3>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Pending</h6>
                            <h3 class="mb-0 text-warning">{{ $allocations->where('approval_status', 'pending')->count() }}</h3>
                        </div>
                        <div class="text-warning" style="font-size: 2rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Rejected</h6>
                            <h3 class="mb-0 text-danger">{{ $allocations->where('approval_status', 'rejected')->count() }}</h3>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="bi bi-x-circle"></i>
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
            <form method="GET" action="{{ route('finance.discounts.allocations.index') }}" class="row g-3">
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select">
                        <option value="">All Terms</option>
                        <option value="1" {{ request('term') == '1' ? 'selected' : '' }}>Term 1</option>
                        <option value="2" {{ request('term') == '2' ? 'selected' : '' }}>Term 2</option>
                        <option value="3" {{ request('term') == '3' ? 'selected' : '' }}>Term 3</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="{{ request('year') }}" placeholder="e.g., 2025">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="approval_status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('approval_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-secondary">
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

        <!-- Allocations Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table"></i> Allocations</h5>
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
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allocations as $allocation)
                            <tr>
                                <td>
                                    @if($allocation->approval_status === 'pending')
                                        <input type="checkbox" name="allocation_ids[]" value="{{ $allocation->id }}" class="allocation-checkbox">
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->student)
                                        <strong>{{ $allocation->student->first_name }} {{ $allocation->student->last_name }}</strong>
                                        <br><small class="text-muted">{{ $allocation->student->admission_number }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->discountTemplate)
                                        <span class="badge bg-info">{{ $allocation->discountTemplate->name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->votehead)
                                        <span class="badge bg-secondary">{{ $allocation->votehead->name }}</span>
                                    @else
                                        <span class="text-muted">All</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>Term {{ $allocation->term }}</strong> / {{ $allocation->year }}
                                    @if($allocation->academicYear)
                                        <br><small class="text-muted">{{ $allocation->academicYear->year }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($allocation->type === 'percentage')
                                        <strong class="text-primary">{{ number_format($allocation->value, 1) }}%</strong>
                                    @else
                                        <strong class="text-primary">Ksh {{ number_format($allocation->value, 2) }}</strong>
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->approval_status === 'pending')
                                        <span class="badge bg-warning"><i class="bi bi-clock"></i> Pending</span>
                                    @elseif($allocation->approval_status === 'approved')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $allocation->created_at->format('d M Y') }}
                                    @if($allocation->creator)
                                        <br><small class="text-muted">by {{ $allocation->creator->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('finance.discounts.show', $allocation) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($allocation->approval_status === 'pending')
                                            <form action="{{ route('finance.discounts.approve', $allocation) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $allocation->id }}" title="Reject">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                        @if($allocation->approval_status === 'approved')
                                            <form action="{{ route('finance.discounts.allocations.reverse', $allocation) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to reverse this discount allocation? This will remove the discount from all related invoices and recalculate them.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $allocation->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Discount Allocation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('finance.discounts.reject', $allocation) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
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
                                    <p class="text-muted mb-0">No allocations found.</p>
                                    <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-plus-circle"></i> Allocate First Discount
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($allocations->hasPages())
            <div class="card-footer">
                {{ $allocations->links() }}
            </div>
            @endif
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.allocation-checkbox');
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

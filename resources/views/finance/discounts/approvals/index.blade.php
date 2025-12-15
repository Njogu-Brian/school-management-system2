@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0">
                <i class="bi bi-check-circle"></i> Discount Approvals
            </h3>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
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
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('finance.discounts.approvals.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Pending Approvals Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
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
                                @if($approval->student)
                                    {{ $approval->student->first_name }} {{ $approval->student->last_name }}
                                    <br><small class="text-muted">{{ $approval->student->admission_number }}</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($approval->discountTemplate)
                                    {{ $approval->discountTemplate->name }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($approval->votehead)
                                    {{ $approval->votehead->name }}
                                @else
                                    <span class="text-muted">All</span>
                                @endif
                            </td>
                            <td>
                                Term {{ $approval->term }} / {{ $approval->year }}
                            </td>
                            <td class="text-end">
                                @if($approval->type === 'percentage')
                                    <strong>{{ number_format($approval->value, 1) }}%</strong>
                                @else
                                    <strong>Ksh {{ number_format($approval->value, 2) }}</strong>
                                @endif
                            </td>
                            <td>
                                <small>{{ $approval->reason }}</small>
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
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this discount?')">
                                            <i class="bi bi-check"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $approval->id }}">
                                        <i class="bi bi-x"></i> Reject
                                    </button>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal{{ $approval->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('finance.discounts.reject', $approval) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Discount</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Enter reason for rejection..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject Discount</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <p class="text-muted mb-0">No pending approvals.</p>
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
</div>
@endsection


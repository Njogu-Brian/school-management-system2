@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">
                        <i class="bi bi-percent"></i> Discounts Dashboard
                    </h3>
                    <p class="text-muted mb-0">Overview of all discount allocations and templates</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Template
                    </a>
                    <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> Allocate Discount
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Quick Navigation Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="{{ route('finance.discounts.templates.index') }}" class="text-decoration-none">
                <div class="card border-primary h-100 hover-shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Templates</h6>
                                <h4 class="mb-0">{{ \App\Models\DiscountTemplate::count() }}</h4>
                                <small class="text-muted">Discount templates</small>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('finance.discounts.allocations.index') }}" class="text-decoration-none">
                <div class="card border-success h-100 hover-shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Allocations</h6>
                                <h4 class="mb-0">{{ \App\Models\FeeConcession::whereNotNull('discount_template_id')->count() }}</h4>
                                <small class="text-muted">Active allocations</small>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="bi bi-list-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('finance.discounts.approvals.index') }}" class="text-decoration-none">
                <div class="card border-warning h-100 hover-shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Pending Approvals</h6>
                                <h4 class="mb-0 text-warning">{{ \App\Models\FeeConcession::where('approval_status', 'pending')->count() }}</h4>
                                <small class="text-muted">Awaiting approval</small>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="text-decoration-none">
                <div class="card border-info h-100 hover-shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Bulk Sibling</h6>
                                <h4 class="mb-0 text-info">Quick</h4>
                                <small class="text-muted">Bulk allocation</small>
                            </div>
                            <div class="text-info" style="font-size: 2.5rem;">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('finance.discounts.templates.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-file-earmark-text"></i> View Templates
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-outline-success w-100">
                                <i class="bi bi-list-check"></i> View Allocations
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('finance.discounts.approvals.index') }}" class="btn btn-outline-warning w-100">
                                <i class="bi bi-check-circle"></i> View Approvals
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="btn btn-outline-info w-100">
                                <i class="bi bi-people"></i> Bulk Sibling
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Allocations -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Allocations</h5>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Template</th>
                                    <th>Value</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($discounts->take(5) as $discount)
                                <tr>
                                    <td>
                                        @if($discount->student)
                                            <strong>{{ $discount->student->first_name }} {{ $discount->student->last_name }}</strong>
                                            <br><small class="text-muted">{{ $discount->student->admission_number }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($discount->discountTemplate)
                                            <span class="badge bg-info">{{ $discount->discountTemplate->name }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($discount->type === 'percentage')
                                            <strong class="text-primary">{{ number_format($discount->value, 1) }}%</strong>
                                        @else
                                            <strong class="text-primary">Ksh {{ number_format($discount->value, 2) }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if($discount->approval_status === 'pending')
                                            <span class="badge bg-warning"><i class="bi bi-clock"></i> Pending</span>
                                        @elseif($discount->approval_status === 'approved')
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>
                                        @else
                                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $discount->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('finance.discounts.show', $discount) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-muted mb-0">No recent allocations.</p>
                                        <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-plus-circle"></i> Allocate Discount
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}
</style>
@endsection

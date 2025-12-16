@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Discounts Dashboard',
        'icon' => 'bi bi-percent',
        'subtitle' => 'Overview of all discount allocations and templates',
        'actions' => '<a href="' . route('finance.discounts.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Create Template</a><a href="' . route('finance.discounts.allocations.index', ['tab' => 'allocate']) . '" class="btn btn-finance btn-finance-success"><i class="bi bi-person-plus"></i> Allocate Discount</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Quick Navigation Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('finance.discounts.templates.index') }}" class="text-decoration-none">
                <div class="finance-stat-card primary finance-animate">
                    <div class="stat-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="stat-value">{{ \App\Models\DiscountTemplate::count() }}</div>
                    <div class="stat-label">Templates</div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('finance.discounts.allocations.index') }}" class="text-decoration-none">
                <div class="finance-stat-card success finance-animate">
                    <div class="stat-icon">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div class="stat-value">{{ \App\Models\FeeConcession::whereNotNull('discount_template_id')->count() }}</div>
                    <div class="stat-label">Allocations</div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('finance.discounts.approvals.index') }}" class="text-decoration-none">
                <div class="finance-stat-card warning finance-animate">
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-value">{{ \App\Models\FeeConcession::where('approval_status', 'pending')->count() }}</div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="text-decoration-none">
                <div class="finance-stat-card info finance-animate">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value">Quick</div>
                    <div class="stat-label">Bulk Sibling</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Allocations -->
    <div class="finance-card finance-animate">
        <div class="finance-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-2"></i> Recent Allocations</span>
            <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-sm btn-finance-outline" style="background: rgba(255,255,255,0.2); color: white; border-color: white;">
                View All
            </a>
        </div>
        <div class="finance-table-wrapper">
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
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
                                            <span class="finance-badge badge-pending"><i class="bi bi-clock"></i> Pending</span>
                                        @elseif($discount->approval_status === 'approved')
                                            <span class="finance-badge badge-approved"><i class="bi bi-check-circle"></i> Approved</span>
                                        @else
                                            <span class="finance-badge badge-rejected"><i class="bi bi-x-circle"></i> Rejected</span>
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
                                    <td colspan="6">
                                        <div class="finance-empty-state">
                                            <div class="finance-empty-state-icon">
                                                <i class="bi bi-percent"></i>
                                            </div>
                                            <h4>No recent allocations</h4>
                                            <p class="text-muted mb-3">Get started by allocating your first discount</p>
                                            <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-finance btn-finance-primary">
                                                <i class="bi bi-plus-circle"></i> Allocate Discount
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

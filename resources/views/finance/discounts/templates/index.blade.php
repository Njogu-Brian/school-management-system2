@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">
                        <i class="bi bi-file-earmark-text"></i> Discount Templates
                    </h3>
                    <p class="text-muted mb-0">Create and manage reusable discount templates</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Template
                    </a>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-outline-success">
                        <i class="bi bi-list-check"></i> Allocations
                    </a>
                    <a href="{{ route('finance.discounts.approvals.index') }}" class="btn btn-outline-warning">
                        <i class="bi bi-check-circle"></i> Approvals
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Total Templates</h6>
                            <h3 class="mb-0">{{ $templates->total() }}</h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Active Templates</h6>
                            <h3 class="mb-0 text-success">{{ $templates->where('is_active', true)->count() }}</h3>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-0">Requires Approval</h6>
                            <h3 class="mb-0 text-info">{{ $templates->where('requires_approval', true)->count() }}</h3>
                        </div>
                        <div class="text-info" style="font-size: 2rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-table"></i> Templates</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Scope</th>
                            <th class="text-end">Value</th>
                            <th>Frequency</th>
                            <th>Requires Approval</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td>
                                <strong>{{ $template->name }}</strong>
                                @if($template->reason)
                                    <br><small class="text-muted">{{ $template->reason }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    {{ ucfirst(str_replace('_', ' ', $template->discount_type)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ ucfirst($template->scope) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($template->type === 'percentage')
                                    <strong class="text-primary">{{ number_format($template->value, 1) }}%</strong>
                                @else
                                    <strong class="text-primary">Ksh {{ number_format($template->value, 2) }}</strong>
                                @endif
                            </td>
                            <td>{{ ucfirst($template->frequency) }}</td>
                            <td>
                                @if($template->requires_approval)
                                    <span class="badge bg-warning"><i class="bi bi-shield-check"></i> Yes</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check"></i> No</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $template->is_active ? 'success' : 'danger' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                {{ $template->created_at->format('d M Y') }}
                                @if($template->creator)
                                    <br><small class="text-muted">by {{ $template->creator->name }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('finance.discounts.allocate') }}?template={{ $template->id }}" class="btn btn-sm btn-primary" title="Allocate">
                                        <i class="bi bi-person-plus"></i>
                                    </a>
                                    <a href="{{ route('finance.discounts.show', $template) }}" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <p class="text-muted mb-0">No templates found.</p>
                                <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-circle"></i> Create First Template
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer">
            {{ $templates->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

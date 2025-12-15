@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-file-earmark-text"></i> Discount Templates
                </h3>
                <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Template
                </a>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Templates Table -->
    <div class="card shadow-sm">
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
                                    <strong>{{ number_format($template->value, 1) }}%</strong>
                                @else
                                    <strong>Ksh {{ number_format($template->value, 2) }}</strong>
                                @endif
                            </td>
                            <td>{{ ucfirst($template->frequency) }}</td>
                            <td>
                                @if($template->requires_approval)
                                    <span class="badge bg-warning">Yes</span>
                                @else
                                    <span class="badge bg-success">No</span>
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
                                <a href="{{ route('finance.discounts.allocate') }}?template={{ $template->id }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-person-plus"></i> Allocate
                                </a>
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


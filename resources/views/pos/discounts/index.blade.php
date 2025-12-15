@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Discounts & Promotions</h1>
            <p class="text-muted mb-0">Manage discounts and promotional codes for the shop</p>
        </div>
        <a href="{{ route('pos.discounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Discount
        </a>
    </div>

    @include('partials.alerts')

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Scope</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($discounts as $discount)
                            <tr>
                                <td>{{ $discount->name }}</td>
                                <td>
                                    @if($discount->code)
                                        <code>{{ $discount->code }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($discount->type) }}</span>
                                </td>
                                <td>
                                    @if($discount->type === 'percentage')
                                        {{ $discount->value }}%
                                    @else
                                        KES {{ number_format($discount->value, 2) }}
                                    @endif
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $discount->scope)) }}</td>
                                <td>{{ $discount->usage_count }} / {{ $discount->usage_limit ?? '∞' }}</td>
                                <td>
                                    @if($discount->isValid())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('pos.discounts.edit', $discount) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('pos.discounts.destroy', $discount) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this discount?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No discounts found</p>
                                    <a href="{{ route('pos.discounts.create') }}" class="btn btn-primary btn-sm">Add First Discount</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($discounts->hasPages())
            <div class="card-footer">
                {{ $discounts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection



